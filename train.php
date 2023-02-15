<?php
( PHP_SAPI !== 'cli' || isset( $_SERVER['HTTP_USER_AGENT'] ) ) && exit;

use ChatWTF\TopCategorizerChatWTF;
use ChatWTF\CategorizerChatWTF;
use ChatWTF\QuestionerChatWTF;
use Orhanerday\OpenAi\OpenAi;

require_once( __DIR__ . "/vendor/autoload.php" );
require_once( __DIR__ . "/db.php" );

$openai = new OpenAi( file_get_contents( __DIR__ . "/api_key.txt" ) );

$questioner = new QuestionerChatWTF( $openai );
$categorizer = new CategorizerChatWTF( $openai );
$topcategorizer = new TopCategorizerChatWTF( $openai );

$trainer = new Trainer(
    db: $db,
    questioner: $questioner,
    categorizer: $categorizer,
    topcategorizer: $topcategorizer,
);

$words = explode( ", ", file_get_contents( "list.txt" ) );
$words = array_unique( $words );
shuffle( $words );

foreach( $words as $word ) {
    $word = trim( rtrim( $word ) );

    echo "Training $word ...\n";
    try {
        $trainer->train( $word );
    } catch (\Throwable $e) {
	echo "ERROR\n";
    }
}
