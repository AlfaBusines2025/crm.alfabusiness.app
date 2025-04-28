<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chatbot Alfa Workflow AI</title>
  <style>
    /* Fondo oscuro y chat centrado */
    html, body {
      margin: 0;
      padding: 0;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      background-color: #1a1a1a;
    }
    /* Contenedor del chat */
    flowise-fullchatbot {
      width: 90vw;
      height: 90vh;
      max-width: 1000px;
      max-height: 800px;
      border-radius: 8px;
      box-shadow: 0 0 20px rgba(0,0,0,0.7);
      background-color: #2a2a2a;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    /* Responsivo para pantallas pequeñas */
    @media (max-width: 768px) {
      flowise-fullchatbot {
        width: 100vw;
        height: 100vh;
        max-width: none;
        max-height: none;
        border-radius: 0;
        box-shadow: none;
      }
    }
  </style>
</head>
<body>
  <flowise-fullchatbot></flowise-fullchatbot>

  <script type="module">
    import Chatbot from "https://cdn.jsdelivr.net/npm/flowise-embed/dist/web.js";

    // Parsear parámetros de URL
    const params = new URLSearchParams(window.location.search);
    const nameBot    = params.get("name_bot")?.trim()    || "Alfa Workflow AI";
    const urlImgBot  = params.get("url_img_bot")?.trim() || "https://crm.alfabusiness.app/media-storage/favicon/6786b5d174e5d---logo-mini-alfa-whatscrm-1.png";
    // Extraer el chatflow ID de la ruta (/chatbot/{id})
    const pathParts       = window.location.pathname.split("/");
    const defaultFlowId   = "{{ $id }}" || "8a12ca4e-57da-4aa2-9cf5-c1ea346d52f4";
    const chatflowidParam = defaultFlowId;

    Chatbot.initFull({
      chatflowid: chatflowidParam,
      apiHost: "https://workflow.alfabusiness.app",
      chatflowConfig: {
      },

      observersConfig: {
        
      },

      theme: {
        button: {
          backgroundColor: '#3B81F6',
          right: 20,
          bottom: 20,
          size: 48,
          dragAndDrop: true,
          iconColor: 'white',
          customIconSrc: urlImgBot,
          autoWindowOpen: {
            autoOpen: true,
            openDelay: 2,
            autoOpenOnMobile: false
          }
        },
        tooltip: {
          showTooltip: true,
          tooltipMessage: '¡Hola! 👋',
          tooltipBackgroundColor: '#000',
          tooltipTextColor: '#fff',
          tooltipFontSize: 16
        },
        disclaimer: {
          title: 'Aviso Legal',
          message: 'Al usar este chatbot, aceptas los <a target="_blank" href="https://flowiseai.com/terms">Términos y Condiciones</a>.',
          textColor: '#000',
          buttonColor: '#3B81F6',
          buttonText: 'Comenzar',
          buttonTextColor: '#fff',
          blurredBackgroundColor: 'rgba(0, 0, 0, 0.4)',
          backgroundColor: '#fff'
        },
        customCSS: ``,
        chatWindow: {
          showTitle: true,
          showAgentMessages: true,
          title: nameBot,
          titleAvatarSrc: urlImgBot,
          welcomeMessage: `¡Hola! Soy ${nameBot}. ¿En qué puedo ayudarte hoy?`,
          errorMessage: 'Lo siento, ocurrió un error. Por favor, inténtalo de nuevo.',
          backgroundColor: '#2a2a2a',
          backgroundImage: '',
          height: '100%',
          width: '100%',
          fontSize: 16,
          starterPrompts: [
            "¿Qué es un bot?",
            "¿Quién eres?"
          ],
          starterPromptFontSize: 15,
          clearChatOnReload: false,
          sourceDocsTitle: 'Documentos:',
          renderHTML: true,
          botMessage: {
            backgroundColor: '#3B3b44',
            textColor: '#e0e0e0',
            showAvatar: true,
            avatarSrc: urlImgBot
          },
          userMessage: {
            backgroundColor: '#3B81F6',
            textColor: '#ffffff',
            showAvatar: true,
            avatarSrc: 'https://raw.githubusercontent.com/zahidkhawaja/langchain-chat-nextjs/main/public/usericon.png'
          },
          textInput: {
            placeholder: 'Escribe tu pregunta aquí...',
            backgroundColor: '#ffffff',
            textColor: '#303235',
            sendButtonColor: '#3B81F6',
            maxChars: 200,
            maxCharsWarningMessage: 'Has excedido el límite de caracteres. Máximo 200.',
            autoFocus: true,
                    sendMessageSound: true,
                    sendSoundLocation: 'send_message.mp3',
                    receiveMessageSound: true,
                    receiveSoundLocation: 'receive_message.mp3'
          },
          feedback: {
            color: '#e0e0e0'
          },
          dateTimeToggle: {
            date: true,
            time: true
          },
          footer: {
            textColor: '#ccc',
            text: 'Hecho por ',
            company: 'Alfa Workflow AI',
            companyLink: 'https://crm.alfabusiness.app'
          }
        }
      }
    });
  </script>
</body>
</html>
