<?php
namespace ChatWTF;

use Orhanerday\OpenAi\OpenAi;

class TopCategorizerChatWTF extends ChatWTF
{
    public function __construct(
        protected OpenAi $open_ai_api,
    ) {
        $this->set_default_prompt( "Select a top level category for the given category out of these choices: person, object, animal, software, other.\n\nThe top level category for 'Activist' is: Person\nThe top level category for 'Philanthropist' is: Person\nThe top level category for 'Color' is: Other\nThe top level category for 'Amphibian' is: Animal\nThe top level category for '{object}' is: " );
    }

    protected function create_prompt( string $question ) {
        return str_replace(
            '{object}',
            $question,
            $this->default_prompt
        );
    }
}
