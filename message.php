<?php
require_once( __DIR__ . "/includes.php" );

header( "Content-Type: application/json" );

if( ! isset( $_SESSION['time'] ) ) {
    $_SESSION['time'] = time();

    die( json_encode( [
        "status" => "init",
    ] ) );
}

if( $_SESSION['time'] >= time() ) {
    die( json_encode( [
        "status" => "wait",
    ] ) );
}

$_SESSION['time'] = time();

function error() {
    die( json_encode( [
        "question_text" => "Sorry, I don't know what you're thinking about",
        "question_id" => 0,
        "category_id" => 0,
        "status" => "success",
        "end" => true,
    ] ) );
}

if ( ! empty( $_SESSION['guess'] ) ) {
    $guess = Guess::from_session( $db );
} else {
    $guess = new Guess( $db );
}

// get answer
$answer = match( $_POST['answer'] ) {
    "yes" => true,
    "no" => false,
    default => null,
};

if ( ! empty( $_POST['category_id'] ) ) {
    $category = Category::load( $_POST['category_id'], $db );

    if( ! $category ) {
        error();
    }

    $guess->answer( $category, $answer );

    ( Guess::DEBUG && Guess::log(
        "Top 10: " . implode( ", ", $guess->top_X( 10 ) )
    ) );

    ( Guess::DEBUG && Guess::log(
        "Best guesses: " . implode( ", ", $guess->best_guesses() )
    ) );
}

// answer question if answer provided
if( $_POST['question_id'] ) {
    // get current question
    $question = Question::load( $_POST['question_id'], $db );

    if( ! $question ) {
        error();
    }
    
    // answer the question
    $guess->answer( $question, $answer );

    ( Guess::DEBUG && Guess::log(
        "Top 10: " . implode( ", ", $guess->top_X( 10 ) )
    ) );

    ( Guess::DEBUG && Guess::log(
        "Best guesses: " . implode( ", ", $guess->best_guesses() )
    ) );
}

// ask for categories first
if ( ! count( $guess->categories ) || count( $guess->best_guesses() ) >= 5 ) {
    // ask a new question
    $category = Category::fetchRandom( $guess, $db );

    if( $category ) {
        $question_text = "Does it belong to the category '" . $category->name . "'?";

        ( Guess::DEBUG && Guess::log( $question_text ) );

        echo json_encode( [
            "question_text" => $question_text,
            "category_id" => $category->id,
            "question_id" => 0,
            "status" => "success",
            "end" => false,
        ] );

        exit;
    }
}

$confidence = $guess->get_confidence();

if ( $confidence >= 12 ) {
    $thing_name = $guess->current_guess()?->get_thing()->name;
    // return response
    echo json_encode([
        "question_text" => "You are thinking about: " . $thing_name,
        "question_id" => 0,
        "category_id" => 0,
        "status" => "success",
        "end" => true,
    ]);

    // empty guess
    $guess->clear();

    exit;
}

// ask a new question
$question = Question::fetchRandom( $guess, $db );

if( ! $question ) {
    // empty guess
    $guess->clear();

    error();
}

( Guess::DEBUG && Guess::log( $question->text ) );

// return response
echo json_encode( [
    "question_text" => $question->text,
    "question_id" => $question->id,
    "category_id" => 0,
    "status" => "success",
    "end" => false,
] );
