
// Inject a button onto the login page:

document.addEventListener( 'DOMContentLoaded' , function() {    
    const loginInWithEthereum = document.createElement( 'BUTTON' );
    loginInWithEthereum.innerText = "Log In With Ethereum";
    loginInWithEthereum.className = 'button button-large login-ethereum'; // First 2 classes are defined in WP
    loginInWithEthereum.addEventListener( 'click', triggerEthereumLogin );
    const currentLoginForm = document.getElementById( 'loginform' );
    currentLoginForm.insertAdjacentElement( 'afterend', loginInWithEthereum );

} );

/**
 * User clicked "Log In with Ethereum";
 */
function triggerEthereumLogin() {
    if ( ! window.ethereum ) {
        //TODO proper error message
        console.warn( 'You need an ethereum wallet installed as an extension' );
        return;
    }
    let nonce = '';
    let address = '';

	window.ethereum.request( { method: 'eth_requestAccounts' } )
	.then( ret => wp.apiFetch( {
        path: "wp-dao/message-to-sign?address=" + ret[0],
        method: 'GET'
    } ) )
	.then( messageToSign => {
        address = messageToSign.address;
        nonce = messageToSign.nonce;

        return window.ethereum.request( {
            method: 'personal_sign',
            params: [
            `0x${toHex( messageToSign.message )}`,
            messageToSign.address,
            ],
        } )
    } )
	.then( signature => {
        // Ok, so we are going to pass data to backend to deal with inside the original login form. We will just add fields to original login form and sumbit.
        var currentLoginForm = document.getElementById( 'loginform' );
        currentLoginForm.appendChild( addLoginData( 'eth_login_address', address ) );
        currentLoginForm.appendChild( addLoginData( 'eth_login_nonce', nonce ) );
        currentLoginForm.appendChild( addLoginData( 'eth_login_signature', signature ) );
        currentLoginForm.submit();
    } );
}

function toHex( stringToConvert ) {
    return stringToConvert
      .split('')
      .map((c) => c.charCodeAt(0).toString(16).padStart(2, '0'))
      .join('');
}

function addLoginData( name, value ) {
    const element = document.createElement( 'INPUT' );
    element.setAttribute( 'type', 'hiddden' );
    element.setAttribute( 'name', name );
    element.value = value;
    return element;
}
