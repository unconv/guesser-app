<?php
/**
 * This script is used to combine similar questions into one
 * using the cosine similarity algorithm
 * 
 * validateSimilarTexts function based on article by Adrian Mihaila:
 * https://medium.com/@adrianmihaila/similarity-between-texts-in-php-e49cb6d1523d
 */

( PHP_SAPI !== 'cli' || isset( $_SERVER['HTTP_USER_AGENT'] ) ) && exit;

use NlpTools\Similarity\CosineSimilarity;
use NlpTools\Tokenizers\WhitespaceTokenizer;

require_once( __DIR__ . "/vendor/autoload.php" );
require_once( __DIR__ . "/db.php" );

function normalize_text( string $text ): string {
    $text = strtolower( $text );

    $text = preg_replace( '/[^a-z ]/', ' ', $text );
    $text = preg_replace( '/\s+/', ' ', $text );
    $text = preg_replace( '/ing\b/', '', $text );
    $text = preg_replace( '/\bare its\b/', 'does it have', $text );
    $text = preg_replace( '/\bare there\b/', 'does it have', $text );

    $text = str_replace( [
        " an ",
        " a ",
        " the ",
        " she ",
        " he ",
        " contain ",
        " require ",
        " for ",
        " also ",
        "open source software",
    ], [
        " ",
        " ",
        " ",
        " it ",
        " it ",
        " have ",
        " need ",
        " to ",
        " also ",
        "open source",
    ], $text );

    return $text;
}

function validateSimilarTexts( $text1, $text2 ) {
    $similarity = new CosineSimilarity();
    $tokenizer = new WhitespaceTokenizer();

    $text1 = normalize_text( $text1 );
    $text2 = normalize_text( $text2 );

    $setA = $tokenizer->tokenize( $text1 );
    $setB = $tokenizer->tokenize( $text2 );

    return $similarity->similarity( $setA, $setB);
}

$stmt = $db->prepare( "SELECT * FROM questions" );
$stmt->execute();

$questions = $stmt->fetchAll( PDO::FETCH_ASSOC );

$num = 1;
$total = count( $questions );
$found = [];

foreach( $questions as $i => $question ) {
    foreach( $questions as $question2 ) {
        if( $question['question_id'] === $question2['question_id'] ) {
            continue;
        }

        if( in_array( $question['question_id'], $found ) ) {
            continue;
        }

        if( in_array( $question2['question_id'], $found ) ) {
            continue;
        }

        if( preg_match( '/[0-9]/', $question['question_text'].$question2['question_text'] ) !== 0 ) {
            continue;
        }

        $similarity = validateSimilarTexts(
            $question['question_text'],
            $question2['question_text']
        );

        if( $similarity > 0.95 ) {
            $found[] = $question['question_id'];
            $found[] = $question2['question_id'];

            echo ($num++) . ": " . $question['question_text'] . " = " . $question2['question_text'] . " (".$i."/".$total.")" .PHP_EOL;

            $question1_length = strlen( $question['question_text'] );
            $question2_length = strlen( $question2['question_text'] );

            if( $question1_length < $question2_length ) {
                $keep = $question;
                $delete = $question2;
            } else {
                $keep = $question2;
                $delete = $question;
            }

            // switch duplicate question id in answers
            $stmt = $db->prepare( "UPDATE answers SET question_id = :new_question_id WHERE question_id = :old_question_id" );
            $stmt->execute( [
                ":new_question_id" => $keep['question_id'],
                ":old_question_id" => $delete['question_id'],
            ] );

            // add question mark to question
            $stmt = $db->prepare( "UPDATE questions SET question_text = :new_question_text WHERE question_id = :new_question_id LIMIT 1" );
            $stmt->execute( [
                ":new_question_text" => trim( $keep['question_text'], "?" ) . "?",
                ":new_question_id" => $keep['question_id'],
            ] );

            // delete old question
            $stmt = $db->prepare( "DELETE FROM questions WHERE question_id = :old_question_id LIMIT 1" );
            $stmt->execute( [
                ":old_question_id" => $delete['question_id'],
            ] );
        }
    }
}
