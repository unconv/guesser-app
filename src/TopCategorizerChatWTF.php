<?php
namespace ChatWTF;

use Orhanerday\OpenAi\OpenAi;

class TopCategorizerChatWTF extends ChatWTF
{
    public function __construct(
        protected OpenAi $open_ai_api,
    ) {
        $this->set_default_prompt( "Select a top level category for the given category out of these choices: person, object, animal, abstract idea, technology. The top level category for '{object}' is:\n" );
    }

    protected function create_prompt( string $question ) {
        return str_replace(
            '{object}',
            $question,
            $this->default_prompt
        );
    }
}
