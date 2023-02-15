const yes = document.querySelector( "#yes" );
const no = document.querySelector( "#no" );
const idk = document.querySelector( "#idk" );
const startover = document.querySelector( "#start-over" );
const message_list = document.querySelector( "#chat-messages" );

let question_id = 0;
let category_id = 0;

yes.addEventListener( "click", function() {
    add_message( "outgoing", "Yes" );
    send_message( "yes" );
} );

no.addEventListener( "click", function() {
    add_message( "outgoing", "No" );
    send_message( "no" );
} );

idk.addEventListener( "click", function() {
    add_message( "outgoing", "I don't know" );
    send_message( "idk" );
} );

startover.addEventListener( "click", function() {
    add_message( "outgoing", "Start over" );
    message_list.innerHTML = "";
    send_message( "start" );
} );

function send_message( answer ) {
    fetch( "message.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "answer=" + encodeURIComponent( answer ) + "&question_id=" + question_id + "&category_id=" + category_id
    } )
    .then( response => response.text() )
    .then( data => {
        const json = JSON.parse( data );
        if( json.status == "success" ) {
            add_message( "incoming", json.question_text );
            question_id = json.question_id;
            category_id = json.category_id;

            if( json.end ) {
                document.querySelectorAll( "#buttons button" ).forEach(
                    e => e.style.display = "none"
                );
                document.querySelector( "#start-over" ).style.display = "inline";
            } else {
                document.querySelectorAll( "#buttons button" ).forEach(
                    e => e.style.display = "inline"
                );
                document.querySelector( "#start-over" ).style.display = "none";
            }
        }
    } )
    .catch( error => {
        add_message( "incoming", "Sorry, there was an error..." );
    } );
}

function add_message( direction, message ) {
    const message_item = document.createElement( "div" );
    message_item.classList.add( "chat-message" );
    message_item.classList.add( direction+"-message" );
    message_item.innerHTML = '<p>' + message + "</p>";
    message_list.appendChild( message_item );
    message_list.scrollTop = message_list.scrollHeight;
    return message_item;
}

function update_message( message, new_message ) {
    message.innerHTML = '<p>' + new_message + "</p>";
    message_list.scrollTop = message_list.scrollHeight;
}

send_message();
