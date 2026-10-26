import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";

// TODO: Replace with your actual Firebase project config
const firebaseConfig = {
  apiKey: "YOUR_API_KEY",
  authDomain: "YOUR_PROJECT_ID.firebaseapp.com",
  projectId: "YOUR_PROJECT_ID",
  storageBucket: "YOUR_PROJECT_ID.appspot.com",
  messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
  appId: "YOUR_APP_ID"
};

const app = initializeApp(firebaseConfig);
const messaging = getMessaging(app);

const VAPID_KEY = "BCJX6tSC-vX4d7gBdxB38sK8waoqFFWpDxTLl9-XO9qXBWi1b12ci8eGjd7acUA_h_kki7ThJuZn-uUzzLakI18";

export const requestFirebaseToken = async () => {
  try {
    const currentToken = await getToken(messaging, { vapidKey: VAPID_KEY });
    if (currentToken) {
      return currentToken;
    } else {
      console.log("No registration token available. Request permission to generate one.");
      return null;
    }
  } catch (err) {
    console.log("An error occurred while retrieving token. ", err);
    return null;
  }
};

export const onMessageListener = () =>
  new Promise((resolve) => {
    onMessage(messaging, (payload) => {
      resolve(payload);
    });
  });

