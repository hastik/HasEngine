<?php namespace ProcessWire;

/**
 * ProcessWire Pro Cache: Hooks
 *
 * Copyright (C) 2020 by Ryan Cramer
 *
 * This is a commercially licensed and supported module
 * DO NOT DISTRIBUTE
 * 
 * @todo status changes should trigger page cache clear
 * @todo separate options for: 
 *  - What should happen when a published page is Unpublished, Hidden, Trashed or Deleted?
 *  - What should happen when a page goes from Visible to Invisible? (by way of being unpublished, hidden, trashed or deleted)
 *  - What should happen when an unpublished, hidden or trashed page is Published, Unhidden or Restored?
 *  - What should happen when a page goes from Invisible to Visible? (by way of being published, unhidden or restored)
 * 
 * @method bool pageStatusChanged(Page $page, $fromStatus, $toStatus)
 *
 */

class ProCacheHooker extends ProCacheClass {

	/**
	 * Add hooks in “init” state
	 * 
	 */
	public function addInitHooks() {
		
		/** @var Pages $pages */
		$pages = $this->wire('pages');
		
		// hook called when a page has been saved
		$pages->addHookAfter('saved', $this, 'hookPageSaved', array('priority' => 200));
		$pages->addHookAfter('savedField', $this, 'hookPageSaved', array('priority' => 200));

		// hook called when a page has been deleted (deletedBranch in 3.0.163+)
		$deleteMethod = method_exists($pages, 'deletedBranch') ? 'deletedBranch' : 'deleted';
		$pages->addHookAfter($deleteMethod, $this, 'hookPageDeleted');
		$pages->addHookAfter('trashed', $this, 'hookPageDeleted');

		// execute cache maintenance tasks every 30 seconds
		$this->addHook('ProcessPageView::finished', $this, 'hookPageViewFinished');

		// hook into template cache clearing
		$this->addHook('PageRender::clearCacheFileAll', $this, 'hookPageRenderClearCacheAll');
		$this->addHook('PageRender::clearCacheFilePages', $this, 'hookPageRenderClearCachePages');
	}

	/**
	 * Add hooks in “ready” state
	 * 
	 */
	public function addReadyHooks() {
	
		/** @var Page $page */
		$page = $this->wire('page');
		
		// add helper notices about ProCache to page and template editors
		if($page->template == 'admin') {
			$this->addHookAfter('ProcessPageEdit::buildFormSettings', $this, 'hookPageEdit');
			// $this->addHookAfter('ProcessTemplateEdit::buildForm', $this, 'hookTemplateEdit'); // @todo
			
		} else if($this->procache->buster) {
			// affects behavior of Pagefile::URL and Pageimage::URL to be ProCache no-cache versions
			$this->addHookBefore('Pagefile::noCacheURL', $this, 'hookPagefileNoCacheURL');
		}

		$this->addHookAfter('Page::render', $this, 'hookPageRender', array('priority' => 200));

		if($this->procache->buster) {
			$this->addHookProperty('Pagefile::busterURL', $this, 'hookPagefileNoCacheURL');
		}
	}
	
	/**
	 * Hook called after a page is rendered
	 *
	 * This is where we apply whatever cache, minify, merge and CDN options were selected
	 *
	 * @param HookEvent $event
	 *
	 */
	public function hookPageRender(HookEvent $event) {

		if(!$this->procache->data('cachePrimed')) return;

		$page = $event->object; /** @var Page $page */
		$config = $this->wire('config'); /** @var Config $config */
		$procache = $this->procache;

		if($procache->timer) {
			$procache->set('pageRenderTime', Debug::timer($procache->timer));
		}
		
		$allowCache = $procache->allowCacheForPage($page);

		// don't proceed if in any kind of admin page
		if($page->template == 'admin' || strpos($page->url, $config->urls->admin) === 0) return;
		if($page->id != $procache->renderPageID && $page->id != $config->http404PageID) return;

		$contentType = $page->template->contentType;
		$isHTML = !$contentType || $contentType === 'html' || $contentType === 'text/html';
		if($isHTML) $isHTML = stripos($event->return, '</html>') !== false;
		$out = $event->return;

		if($isHTML) {
			// minify, merge and CDN options only apply to HTML documents
			$allowMinify = true;
			if(in_array('useTemplates', $procache->minifyOptions)) {
				$allowMinify = in_array($page->template->id, $procache->minifyTemplates);
			}
			// option to disable minify via a "NoMinify" get var, for users that are allowed (for debugging)
			if($allowMinify && !empty($_GET['NoMinify']) && $this->wire('user')->isLoggedin() && $page->editable()) $allowMinify = false;
			$allowMerge = $allowMinify;
			$n = 0;
			if($procache->getTweaks()->renderOutputTweaksEarly($page, $out)) $n++;
			if($allowMerge) if($procache->getMinify()->renderMerge($page, $out)) $n++;
			if($procache->renderCDN($page, $out)) $n++; // checks if allowed already
			if($allowMinify) if($procache->getMinify()->renderMinify($page, $out, $allowCache)) $n++;
			if($procache->getTweaks()->renderOutputTweaks($page, $out)) $n++;
			if($n) $event->return = $out;
		}

		if($allowCache) $procache->getStatic()->renderCache($page, $out);
	}

	/**
	 * Cache clearing to occur when a page is saved
	 *
	 * @param HookEvent $event
	 *
	 */
	public function hookPageSaved(HookEvent $event) {
		/** @var Page $page */
		$page = $event->arguments[0];
		$this->pageSaved($page);
	}

	/**
	 * Implementation for hookPageSaved that can also be called manually for testing
	 * 
	 * @param Page $page
	 * 
	 */
	public function pageSaved(Page $page) {

		$status = $page->status;
		$statusPrevious = $page->statusPrevious;
		
		if($statusPrevious !== null && $status !== $statusPrevious) {
			// status changed
			if($this->pageStatusChanged($page, $statusPrevious, $status)) return;
		}

		if($page->hasStatus(Page::statusDraft)) {
			if(!$page->statusPrevious || $page->statusPrevious == $page->status) {
				$this->procache->clearPage($page); // just in case it was published before
				return;
			} else {
				// fall through to same rules as if it was published, since status may have just changed
			}
		}

		if($page->template->cache_time < 0) {
			// already handled by one of the PageRender::clearCacheFile hooks
			return;
		}

		$cleared = $this->procache->getStatic()->executeCacheClearBehaviors($page);
		$clearedChildren = !empty($cleared['site']) || !empty($cleared['family']) || !empty($cleared['children']);

		if($page->parentPrevious) {
			// if page had a previous parent, clear it too
			$this->procache->getStatic()->clearBranch($page->parentPrevious);
			if(!$clearedChildren) $this->procache->getStatic()->clearBranch($page->parent);
			$clearedChildren = true;
		}

		if($page->namePrevious) {
			// if page's name changed, clearPage already wiped out everything below it
			// but clear the immediate parent too, as a bonus
			if(!$clearedChildren) $this->procache->getStatic()->clearBranch($page->parent);
		}
	}

	/**
	 * Called when page status has changed
	 * 
	 * @param Page $page
	 * @param $fromStatus
	 * @param $toStatus
	 * @return bool Return true if cache clearing was handled and can be skipped, false if not
	 * 
	 */
	public function ___pageStatusChanged(Page $page, $fromStatus, $toStatus) {
		if($page) {} // ignore
		
		if($toStatus & Page::statusUnpublished) {
			// page is unpublished
			if($fromStatus & Page::statusUnpublished) {
				// no change to unpublished status, cache clearing can be skipped
				return true;
			} else {
				// page was just unpublished, do regular cache clearing behaviors
			}
		}
		
		if($toStatus & Page::statusHidden) {
			if($fromStatus & Page::statusHidden) {
				// no change to hidden status
			} else {
				// page was just hidden
			}	
		}
		
		return false;
	}

	/**
	 * Handle a deleted page
	 *
	 * @param HookEvent $event
	 *
	 */
	public function hookPageDeleted(HookEvent $event) {
		$page = $event->arguments(0);
		$this->procache->getStatic()->clearBranch($page);
	}

	/**
	 * Hook to PageRender::clearCacheFileAll (PW 2.6.9+)
	 *
	 * @param HookEvent $event
	 *
	 */
	public function hookPageRenderClearCacheAll(HookEvent $event) {
		$page = $event->arguments(0);
		if($page->template->cache_time < 0) {
			$this->procache->clearAll();
		}
	}

	/**
	 * Hook to PageRender::clearCacheFilePages (PW 2.6.9+)
	 *
	 * @param HookEvent $event
	 *
	 */
	public function hookPageRenderClearCachePages(HookEvent $event) {
		/** @var PageArray $items */
		$items = $event->arguments(0);
		/** @var Page $page */
		$page = $event->arguments(1);
		if($page->template->cache_time >= 0) return; // not handled by ProCache
		if(!$items->has($page)) $this->procache->clearPage($page);
		$this->procache->clearPages($items);
	}
	
	/**
	 * Hook into ProcessPageEdit::buildFormSettings to add note about ProCache
	 *
	 * @param HookEvent $event
	 *
	 */
	public function hookPageEdit(HookEvent $event) {

		/** @var WirePageEditor $process */
		$process = $event->object;
		$page = $process->getPage();
		$form = $event->return;
		$info = ProCache::getModuleInfo();

		/** @var InputfieldMarkup $f */
		$f = $this->modules->get('InputfieldMarkup');
		$f->attr('id+name', 'ProCacheInfo');
		$f->label = $info['title'];
		$info = $this->procache->pageInfo($page);

		if($info === false) {
			$f->attr('value', $this->_('This page is not cached by ProCache.'));
			$f->collapsed = Inputfield::collapsedYes;

		} else if(!count($info)) {
			$f->attr('value', $this->_('This page is set to be cached, but no cache files currently exist.'));

		} else {
			$f->description = $this->_('The following files are currently cached for this page.');
			/** @var MarkupAdminDataTable $table */
			$table = $this->modules->get('MarkupAdminDataTable');
			$table->headerRow(array($this->_('Cached URL'), $this->_('Date/Time Cached')));
			foreach($info as $url => $created) {
				$table->row(array($url, $created));
			}
			$f->attr('value', $table->render());
		}

		$form->add($f);
	}

	/**
	 * Hook replacing Pagefile::noCacheURL
	 *
	 * @param HookEvent $event
	 *
	 */
	public function hookPagefileNoCacheURL(HookEvent $event) {
		/** @var Pagefile $pagefile */
		$pagefile = $event->object;
		$http = $event->arguments(0) === true;
		$event->return = $this->procache->getBuster()->url($http ? $pagefile->httpUrl() : $pagefile->url(), array(
			'file' => $pagefile->filename
		));
		$event->replace = true;
	}

	/**
	 * Hook into ProcessTemplate::buildForm
	 *
	 * @todo: finish this
	 * @param HookEvent $event
	 *
	 */
	public function hookTemplateEdit(HookEvent $event) {
		$form = $event->return;
		// $template = $event->arguments[0];
		$field = $form->get("cache_time");
		if(!$field) return;

		/** @var InputfieldMarkup $f */
		$f = $this->modules->get('InputfieldMarkup');
		$f->attr('id+name', 'ProCacheInfo');
		$info = ProCache::getModuleInfo();
		$f->label = $info['title'];
		// to be added	
	}
	
	/**
	 * Cache maintenance to occur every 30 seconds
	 *
	 * 
	 * @param HookEvent $event
	 */
	public function hookPageViewFinished(HookEvent $event) {
		if($event) {} // ignore
		$this->procache->getStatic()->cacheMaintenance();
	}


	
}
	
