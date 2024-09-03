function fallbackCopyTextToClipboard(text) {
    let textArea = document.createElement("textarea");
    textArea.value = text;

    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        let successful = document.execCommand('copy');
        let msg = successful ? 'successful' : 'unsuccessful';
        console.log('Fallback: Copying text command was ' + msg);
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
    }

    document.body.removeChild(textArea);
}
export function copyTextToClipboard(text) {
    if (!navigator.clipboard) {
        fallbackCopyTextToClipboard(text);
        return;
    }
    navigator.clipboard.writeText(text).then(function() {
    }, function(err) {
        console.error('Async: Could not copy text: ', err);
    });
}

export function mappingLanguage(language){
    let lang = null;
    switch (language) {
        case 'Castellano':
        lang = 'castellano';
        break;
        case 'Spanish':
        lang = 'castellano';
        break;
        case 'Castellà':
        lang = 'castellano';
        break;
        case 'Inglés':
        lang = 'ingles';
        break;
        case 'English':
        lang = 'ingles';
        break;
        case 'Anglés':
        lang = 'ingles';
        break;
        case 'Valenciano':
        lang = 'valenciano';
        break;
        case 'Valencian':
        lang = 'valenciano';
        break;
        case 'Valencià':
        lang = 'valenciano';
        break;
        default:
        lang = null;
        break;
    }
    return lang;
}


