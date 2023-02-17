<?php
use League\CommonMark\CommonMarkConverter;

class ChatWTFResponse
{
    public function __construct(
        public string $question,
        public string $answer,
    ) {}

    public function get_formatted_answer(): string {
        $converter = new CommonMarkConverter();
        $styled = $converter->convert( $this->answer );

        return (string) $styled;
    }
}
