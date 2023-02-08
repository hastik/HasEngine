<p>
These <a target='_blank' href='{url}'>Apache .htaccess directives</a>
are for optimizing performance with expires 
headers and GZIP compression. If using a different server software (like nginx) these
directives will not work, but can be translated to nginx equivalents.
</p>
<p>
Note that these tweaks are entirely separate from the .htaccess adjustments that 
ProCache makes for delivering cached pages. The additions outlined here are all 
optional and intended as a starting point for you to tweak further as needed.
However they can make a big difference in the performance of your site and their
value should not be underestimated. In particular, adding GZIP compression is strongly
recommended (and our tweaks below include this). 
</p>

<p><strong>Update your .htaccess file</strong></p>

<ol style='margin-left: 1em;'>
	
	<li>
		<p>
		Place the contents of <a target='_blank' href='{url}'>this file</a>
		at the top of your .htaccess file, above the existing ProcessWire .htaccess directives.
		</p>
	</li>
	
	<li>
		<p>
		Modify the expiration times on the "ExpiresByType" lines, if desired for your needs.
		The default values present are those we use for processwire.com, which may or may
		not be consistent with the needs of your site, but they are a good starting point.
		</p>
	</li>
	
	<li>
		<p>
		After placing the recommended tweaks in your .htaccess file, reload your site to make 
		sure you aren't getting a 500 error. If you are getting an error, try uncommenting the two lines near the 
		end that indicate they may need to be uncommented for Apache versions 2.3.7 and newer.
		(Note: a commented line is one that begins with a "#"). If you continue
		to get a 500 error, re-comment those lines and comment out entire sections of the file
		until you determine what lines are not compatible with your server. You may need to ask
		your web host to enable certain features for your account.
		</p>
	</li>
		
	<li>
		<p>
		These performance tweaks are based largely upon the .htaccess file from HTML5 Boilerplate,
		but with several adjustments and removals. See the
		<a target='_blank' href='https://github.com/h5bp/html5-boilerplate/blob/master/dist/.htaccess'>
		HTML5 Boilerplate .htaccess file</a> (external link) for more options that you may wish to add or refine further.
		</p>
	</li>
		
</ol>

<p><strong>Test your site performance</strong></p>

<ol style='margin-left: 1em;'>
	<li>
		<p>
		<a class='pwpc-tests' href='_testKeepalive'>Test that keep-alive is working</a>.
		If it is not working, inquire with your web host about enabling "Connection: keep-alive" in Apache. 
		</p>
	</li>
		
	<li>
		<p>
		<a class='pwpc-tests' href='_testCompression'>Test that GZIP is working</a>.
		If it is not working, inquire with your web host about enabling mod_deflate in Apache. 
		</p>
	</li>
	
	<li>
		<p>
		<a target='_blank' href='https://gtmetrix.com/add-expires-headers.html'>Check that your
		"Expires" headers are working</a> (external link to gtmetrix.com). In the results, see the "Leverage browser caching" 
		(for Page Speed) and/or the "Add Expires Headers (for YSlow). Click either for details.
		Note that you cannot control the Expires headers for external resources like Google 
		Analytics, Typekit, and so on.
		</p>
	</li>
	
	<li>
		<p>
		<a target='_blank' href='https://developers.google.com/speed/pagespeed/insights/'>Google PageSpeed Insights</a>,
		<a target='_blank' href='https://gtmetrix.com/'>GTMetrix</a> and
		<a target='_blank' href='https://www.webpagetest.org'>Web Page Test</a>
		are useful tools for testing the performance of your website.
		</p>
	</li>
</ol>
<script>
	$(document).ready(function() {
		$('a.pwpc-tests').on('click', function() {
			if(typeof Inputfields == "undefined") return;
			Inputfields.find($(this).attr('href'));
			return false;
		}); 
	}); 
</script>

	
