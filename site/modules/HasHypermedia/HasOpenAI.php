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