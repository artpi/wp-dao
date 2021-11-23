
// Inject a button onto the login page:
const ethsvg = '<svg style="height: 30px;float:left;margin-right:20px;" xmlns="http://www.w3.org/2000/svg" clip-rule="evenodd" fill-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.41421" viewBox="170 30 220 350"><g fill-rule="nonzero" transform="matrix(.781253 0 0 .781253 180 37.1453)"><path d="m127.961 0-2.795 9.5v275.668l2.795 2.79 127.962-75.638z" fill="#343434"></path><path d="m127.962 0-127.962 212.32 127.962 75.639v-133.801z" fill="#8c8c8c"></path><path d="m127.961 312.187-1.575 1.92v98.199l1.575 4.601 128.038-180.32z" fill="#3c3c3b"></path><path d="m127.962 416.905v-104.72l-127.962-75.6z" fill="#8c8c8c"></path><path d="m127.961 287.958 127.96-75.637-127.96-58.162z" fill="#141414"></path><path d="m.001 212.321 127.96 75.637v-133.799z" fill="#393939"></path></g></svg>';

document.addEventListener( 'DOMContentLoaded' , function() {    
    const loginInWithEthereum = document.createElement( 'BUTTON' );
    loginInWithEthereum.innerHTML = ethsvg + "<div style='line-height:30px; text-transform:uppercase; float:right; font-weight:bold'>Sign In with Ethereum</div>";
    loginInWithEthereum.className = 'button button-large login-ethereum'; // First 2 classes are defined in WP
    loginInWithEthereum.addEventListener( 'click', triggerEthereumLogin );
    loginInWithEthereum.style.padding = '20px';
    loginInWithEthereum.style.margin = '30px auto';
    loginInWithEthereum.style.display = 'block';    
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
        path: "dao-login/message-to-sign?address=" + ret[0],
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
