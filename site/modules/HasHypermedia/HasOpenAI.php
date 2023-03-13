<?php namespace ProcessWire;
    use OpenAI;

    class HasAI{
        public $key;
    }

    class HasOpenAI extends HasAI{



        function fetch($prompt){

            $client = OpenAI::client('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');
          
            $result = $client->completions()->create([
                'model' => 'text-davinci-003',
                'max_tokens' => 1500,
                'prompt' => $prompt
            ]);

            return $result;

        }

        function dummyFetch($prompt){
            return "To je velice zajímavé. Hmmm. - automatická dummy odpověď";
        }

    }


?>