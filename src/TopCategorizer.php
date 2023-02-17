<?php
use Orhanerday\OpenAi\OpenAi;

class TopCategorizer extends ChatWTF
{
    public function __construct(
        protected OpenAi $open_ai_api,
    ) {
        $this->set_default_prompt( "Select a top level category for the given thing out of these choices: 'Person or Character', 'Object', 'Animal', 'Other'.\n\nThe top level category for 'Activist' is:\nPerson or Character\n\nThe top level category for 'Philanthropist' is:\nPerson or Character\n\nThe top level category for 'Color' is:\nOther\n\nThe top level category for 'Amphibian' is:\nAnimal\n\nThe top level category for 'Vehicle' is: Object\n\nThe top level category for '{object}' is:\n" );
    }

    protected function create_prompt( string $question ) {
        return str_replace(
            '{object}',
            $question,
            $this->default_prompt
        );
    }
}
