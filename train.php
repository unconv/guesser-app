<?php
/**
 * This script is used to train the guesser with OpenAI
 *
 * It reads a list of words to train from list.txt
 * The file can be comma, semicolon or newline separated
 */

( PHP_SAPI !== 'cli' || isset( $_SERVER['HTTP_USER_AGENT'] ) ) && exit;

use Orhanerday\OpenAi\OpenAi;

require_once( __DIR__ . "/vendor/autoload.php" );
require_once( __DIR__ . "/db.php" );

$openai = new OpenAi( file_get_contents( __DIR__ . "/api_key.txt" ) );

$questioner = new Questioner( $openai );
$categorizer = new Categorizer( $openai );
$topcategorizer = new TopCategorizer( $openai );

$trainer = new Trainer(
    db: $db,
    questioner: $questioner,
    categorizer: $categorizer,
    topcategorizer: $topcategorizer,
);

$words = preg_split( "/[,;\n]/", file_get_contents( "list.txt" ) );
$words = array_unique( $words );
shuffle( $words );

echo "Training " . count( $words ) . " words...\n";

foreach( $words as $word ) {
    $word = trim( rtrim( $word ) );

    echo "Training $word ...\n";

    try {
        $trainer->train( $word );
    } catch (\Throwable $e) {
	    echo "ERROR\n";
    }
}
