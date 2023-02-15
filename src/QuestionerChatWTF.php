<?php
namespace ChatWTF;

use Orhanerday\OpenAi\OpenAi;

class QuestionerChatWTF extends ChatWTF
{
    public function __construct(
        protected OpenAi $open_ai_api,
    ) {
        $this->set_default_prompt( "Create 15 questions that would be answered 'Yes' about an object / item / person / thing. 
        Only use it/he/she to refer to the thing in the question.
        Don't use its name in the questions. Make at least a
        couple of the questions specific to that thing so that
        the answer would be 'No' if you were to ask it about another thing. List only questions, one per line, with no numberings.\n\nThing: {object}\n10 questions that would be answered with 'Yes' about '{object}':\n" );
    }

    protected function create_prompt( string $question ) {
        return str_replace(
            '{object}',
            $question,
            $this->default_prompt
        );
    }
}
