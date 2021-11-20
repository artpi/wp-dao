
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

	window.ethereum.request( { method: 'eth_requestAccounts' } )
	.then( ret => wp.apiFetch( {
        path: "wp-dao/message-to-sign?address=" + ret[0],
        method: 'GET'
    } ) )
	.then( messageToSign => window.ethereum.request( {
        method: 'personal_sign',
        params: [
          `0x${toHex( messageToSign.message )}`,
          messageToSign.address,
        ],
      } ) )
	.then( signature => console.log( signature ) )
}

function toHex( stringToConvert ) {
    return stringToConvert
      .split('')
      .map((c) => c.charCodeAt(0).toString(16).padStart(2, '0'))
      .join('');
}
