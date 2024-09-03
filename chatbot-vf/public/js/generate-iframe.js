let parameter = document.currentScript.getAttribute('chatbot-id');
let language = document.currentScript.getAttribute('lang');

let scriptUrl = document.currentScript.src;
let a = document.createElement('a');
a.href = scriptUrl;

let domain = a.protocol + '//' + a.hostname + (a.port ? ':' + a.port : '');
let chatbotUrl = domain + '/chatbot-customer/' + parameter;
if(language) chatbotUrl = chatbotUrl + '/' + language;

const defaultHeight = "75vh";
const defaultWidth = "452px";
const defaultHeightMinimize = "69px";
const defaultWidthMinimize = "69px";
let chatIsOpen = false;

function createChatbotContainer() {
    let chatbotContainer = document.createElement("div");
    chatbotContainer.id = "chatbot-container";
    chatbotContainer.style.position = "fixed";
    chatbotContainer.style.width = defaultWidthMinimize;
    chatbotContainer.style.height = defaultHeightMinimize;
    chatbotContainer.style.zIndex = "2147483000";
    chatbotContainer.style.right = "6px";
    chatbotContainer.style.bottom = "6px";

    let iframeWrapper = document.createElement("div");
    iframeWrapper.id = "iframe-wrapper";
    iframeWrapper.style.width = "100%";
    iframeWrapper.style.height = "100%";
    iframeWrapper.style.backgroundColor = "transparent";
    iframeWrapper.frameBorder = "0";
    iframeWrapper.allowTransparency="true";

    document.body.appendChild(chatbotContainer);
    chatbotContainer.appendChild(iframeWrapper);

    createChatbotIframe(iframeWrapper);
    setMediaQuerys(chatbotContainer);

}

function createChatbotIframe(wrapper) {
    let iframe = document.createElement("iframe");

    iframe.setAttribute("id", "chat-" + parameter);
    iframe.setAttribute("src", chatbotUrl);
    iframe.setAttribute("width", "100%");
    iframe.setAttribute("height", "100%");
    iframe.setAttribute("frameborder", "0");
    iframe.setAttribute("allow", "microphone *");
    iframe.style.borderRadius = "1em";
    iframe.style.border = "none";
    wrapper.appendChild(iframe);

}

function openChat() {
    let containerIframe =  document.getElementById("chatbot-container");

    if (window.innerWidth <= 768) {
        containerIframe.style.height = "98vh";
        containerIframe.style.width = "95vw";
    } else {
        containerIframe.style.height = defaultHeight;
        containerIframe.style.width = defaultWidth;
    }

    chatIsOpen = true;
}

function closeChat() {
    let containerIframe =  document.getElementById("chatbot-container");

    containerIframe.style.height = defaultHeightMinimize;
    containerIframe.style.width = defaultWidthMinimize;

    chatIsOpen = false;
}

function maximizeChat() {
    let containerIframe =  document.getElementById("chatbot-container");

    if (window.innerWidth <= 768) {
        containerIframe.style.width = "95vw";
    }else{
        containerIframe.style.width = "97vw";
    }
    containerIframe.style.height = "98vh";
}

function minimizeChat() {
    let containerIframe =  document.getElementById("chatbot-container");

    if (window.innerWidth <= 768) {
        containerIframe.style.height = "98vh";
        containerIframe.style.width = "95vw";
    } else {
        containerIframe.style.height = defaultHeight;
        containerIframe.style.width = defaultWidth;
    }
}

function setMediaQuerys(chatbotContainer){
    window.addEventListener('resize', function() {
        if (window.matchMedia("(max-width: 768px)").matches) {
            if(chatIsOpen){
                chatbotContainer.style.height = "98vh";
                chatbotContainer.style.width = "95vw";
            }
        }else{
            if(chatIsOpen){
                chatbotContainer.style.height = defaultHeight;
                chatbotContainer.style.width = defaultWidth;
            }
        }
    });
}

window.onload = createChatbotContainer;

window.addEventListener('message', event => {
    if (typeof event.data === 'string') {
        switch (event.data) {
            case 'openChat':
                openChat();
                break;
            case 'closeChat':
                closeChat();
                break;
            case 'maximizeChat':
                maximizeChat();
                break;
            case 'minimizeChat':
                minimizeChat();
                break;
            default:
        }
    }
});
