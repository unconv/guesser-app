<?php
namespace ChatWTF;

use Orhanerday\OpenAi\OpenAi;

class CategorizerChatWTF extends ChatWTF
{
    public function __construct(
        protected OpenAi $open_ai_api,
    ) {
        $this->set_default_prompt( "Create a list of 1-5 categories for the given thing, for example: celebrity, houshold item, singer, actor, actress, musician, tool, animal, pet. Give the category as a singular noun. Don't add numberings. One category per line.\n\nThing: zebra\nThe categories for 'zebra' are:\n- Animal\n- Exotic animal\n\nThing: computer keyboard\nThe categories for 'computer keyboard' are:\n- Computer accessory\n- Technology\n\nThing: airplane\nThe categories for 'airplane' are:\n- Aircraft\n- Technology\n- Large machines\n\nThing: {object}\nThe categories for '{object}' are:\n" );
    }

    protected function create_prompt( string $question ) {
        return str_replace(
            '{object}',
            $question,
            $this->default_prompt
        );
    }
}
