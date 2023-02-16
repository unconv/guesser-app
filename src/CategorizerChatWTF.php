<?php
namespace ChatWTF;

use Orhanerday\OpenAi\OpenAi;

class CategorizerChatWTF extends ChatWTF
{
    public function __construct(
        protected OpenAi $open_ai_api,
    ) {
        $this->set_default_prompt( "Create a list of 10 categories for the given thing, for example: celebrity, household item, singer, actor, actress, musician, tool, animal, pet or politician. Give the category as a singular noun. Don't add numberings. One category per line.\nThe categories for '{object}' are:\n" );
    }

    protected function create_prompt( string $question ) {
        return str_replace(
            '{object}',
            $question,
            $this->default_prompt
        );
    }
}
