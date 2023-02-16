<?php
namespace ChatWTF;

use Orhanerday\OpenAi\OpenAi;

class CategorizerChatWTF extends ChatWTF
{
    public function __construct(
        protected OpenAi $open_ai_api,
    ) {
        $this->set_default_prompt( "Create a semicolon separated list of categories for the given thing, for example: celebrity, household item, singer, actor, actress, musician, tool, animal, person, pet or politician. Give the category as a singular noun. Use simple words that anyone would know. The category must have an 'is a' relationship with the object so it must fit the sentence '<Object> is a <Category>'. Don't add numberings. \nThe categories for '{object}' are:\n" );
    }

    protected function create_prompt( string $question ) {
        return str_replace(
            '{object}',
            $question,
            $this->default_prompt
        );
    }
}
