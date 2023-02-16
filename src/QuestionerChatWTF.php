<?php
namespace ChatWTF;

use Orhanerday\OpenAi\OpenAi;

class QuestionerChatWTF extends ChatWTF
{
    public function __construct(
        protected OpenAi $open_ai_api,
    ) {
        $this->set_default_prompt( "Generating questions for a game that tries to guess by yes or no questions what the user is thinking about. Create 20 questions that would be answered 'Yes' about an object / item / person / thing. 
        Only use 'it' to refer to the thing in the questions.
        Don't use its name in the questions. Make at least half
        of the questions specific to that thing so that
        the answer would be 'No' if you were to ask it about another thing. If the thing is a known person, the first question should be 'Is it a man?' or 'Is it a woman?'. Use simple English. List only questions, one per line, with no numberings.\n\nThing: {object}\n20 questions that would be answered with 'Yes' about '{object}':\n" );
    }

    protected function create_prompt( string $question ) {
        return str_replace(
            '{object}',
            $question,
            $this->default_prompt
        );
    }
}
