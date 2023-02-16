<?php
require_once( __DIR__ . "/vendor/autoload.php" );
require_once( __DIR__ . "/error_handler.php" );
require_once( __DIR__ . "/db.php" );

session_start();

header( "Content-Type: application/json" );

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

    Guess::log(
        "Top 10: " . implode( ", ", $guess->top_X( 10 ) )
    );

    Guess::log(
        "Best guesses: " . implode( ", ", $guess->best_guesses() )
    );
}

// ask for categories first
if ( ! count( $guess->categories ) || count( $guess->best_guesses() ) >= 5 ) {
    // ask a new question
    $category = Category::fetchRandom( $guess, $db );

    if( $category ) {
        echo json_encode( [
            "question_text" => "Does it belong to the category '" . $category->name . "'?",
            "category_id" => $category->id,
            "question_id" => 0,
            "status" => "success",
            "end" => false,
        ] );

        exit;
    }
}

$confidence = $guess->get_confidence();

if (
    $confidence >= 8 && count( $guess->questions ) > 0 ||
    $confidence >= 7 && count( $guess->questions ) > 15 ||
    $confidence >= 6 && count( $guess->questions ) > 20 ||
    $confidence >= 6 && count( $guess->questions ) > 25 ||
    $confidence >= 5 && count( $guess->questions ) > 35 ||
    $confidence >= 4 && count( $guess->questions ) > 40
) {
    $thing_name = $guess->current_guess()?->get_thing()->name;
    // return response
    echo json_encode([
        "question_text" => "You are thinking about: " . $thing_name . " (".$confidence.")",
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

// return response
echo json_encode( [
    "question_text" => $question->text . " (".$confidence.")",
    "question_id" => $question->id,
    "category_id" => 0,
    "status" => "success",
    "end" => false,
] );
