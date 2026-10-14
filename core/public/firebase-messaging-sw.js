// public/firebase-messaging-sw.js

importScripts('https://www.gstatic.com/firebasejs/9.22.2/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.22.2/firebase-messaging-compat.js');

const firebaseConfig = {
    apiKey: "{{ env('apiKey') }}",
    authDomain: "{{ env('authDomain') }}",
    projectId: "{{ env('projectId') }}",
    storageBucket: "{{ env('storageBucket') }}",
    messagingSenderId: "{{ env('messagingSenderId') }}",
    appId: "{{ env('appId') }}",
    measurementId: "{{ env('measurementId') }}"
};

firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

// Optional: handle background messages
messaging.onBackgroundMessage(function(payload) {
  console.log('Message received. ', payload);
  console.log('[firebase-messaging-sw.js] Received background message ', payload);

  const notificationTitle = payload.notification.title;
  const notificationOptions = {
    body: payload.notification.body,
    icon: '/icon.png' // Optional icon
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});
