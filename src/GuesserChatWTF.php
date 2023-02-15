<?php
namespace ChatWTF;

use Orhanerday\OpenAi\OpenAi;

class GuesserChatWTF extends ChatWTF
{
    public function __construct(
        protected OpenAi $open_ai_api,
    ) {
        $this->set_default_prompt( "" );

        $sample_questions = [
            new ChatWTFResponse(
                answer: "an elephant",
                question: "Is it an animal? Yes. Is it big? Yes. Is it grey? Yes. Does it have a trunk? Yes. Can you ride it? Yes.",
            ),
            new ChatWTFResponse(
                answer: "a table",
                question: "Is it an object? Yes. Does it have feet? Yes. Does it stand on its own? Yes. Can you eat from it? Yes.",
            ),
            new ChatWTFResponse(
                answer: "a door",
                question: "Is it a part of a house? Yes. Can you walk through it? Yes. Can you open it? Yes.",
            ),
            new ChatWTFResponse(
                answer: "Conan O'Brien",
                question: "Is it a person? Yes. Is it a celebrity? Yes. Is it a comedian? Yes. Does it have its own talk show? Yes.",
            ),
        ];

        $this->set_sample_questions( $sample_questions );
    }
}
