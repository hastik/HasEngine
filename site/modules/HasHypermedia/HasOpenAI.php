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

        function fetchChat($prompt){

            $client = OpenAI::client('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');
          
            $response = $client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'temperature' => 0,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);
            
            $response->id; // 'chatcmpl-6pMyfj1HF4QXnfvjtfzvufZSQq6Eq'
            $response->object; // 'chat.completion'
            $response->created; // 1677701073
            $response->model; // 'gpt-3.5-turbo-0301'
            
            foreach ($response->choices as $result) {
                $result->index; // 0
                $result->message->role; // 'assistant'
                $result->message->content; // '\n\nHello there! How can I assist you today?'
                $result->finishReason; // 'stop'
            }
            
            $response->usage->promptTokens; // 9,
            $response->usage->completionTokens; // 12,
            $response->usage->totalTokens; // 21
            
            $response->toArray(); // ['id' => 'chatcmpl-6pMyfj1HF4QXnfvjtfzvufZSQq6Eq', ...]

            return $response;

        }


        function dummyFetch($prompt){
            return "To je velice zajímavé. Hmmm. - automatická dummy odpověď";
        }

    }


?>