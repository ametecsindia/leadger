<html lang="{{ @Helper::currentLanguage()->code }}" dir="{{ @Helper::currentLanguage()->direction }}">
<head>
    @include('dashboard.layouts.head')

    <!-- Firebase SDKs -->
    <!-- <script src="https://www.gstatic.com/firebasejs/9.6.10/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.10/firebase-messaging-compat.js"></script> -->
</head>
<body>
<div class="app" id="app">
    @php($webmailsNewCount = Helper::webmailsNewCount())
    @include('dashboard.layouts.menu')

    <div id="content" class="app-content box-shadow-z0" role="main">
        @include('dashboard.layouts.header')
        <div ui-view class="app-body" id="view">
            @include('dashboard.layouts.errors')
            @yield('content')
        </div>
        @include('dashboard.layouts.footer')
    </div>

    @include('dashboard.layouts.settings')
</div>
@include('dashboard.layouts.foot')
    <script src="https://www.gstatic.com/firebasejs/9.6.10/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.10/firebase-messaging-compat.js"></script>
<script>
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

  function sendTokenToServer(token) {
    fetch('/save-fcm-token', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ token })
    })
    .then(res => res.json())
    .then(data => console.log('Token saved:', data))
    .catch(err => console.error('Error saving token:', err));
  }

  function initFirebaseMessagingRegistration() {
    if (!('Notification' in window)) {
      console.warn('This browser does not support notifications.');
      return;
    }

    if (Notification.permission === 'granted') {
      registerServiceWorkerAndGetToken();
    } else if (Notification.permission !== 'denied') {
      Notification.requestPermission().then(permission => {
        console.log('Notification permission:', permission);
        if (permission === 'granted') {
          registerServiceWorkerAndGetToken();
        } else {
          console.warn('Notification permission not granted');
        }
      });
    }
  }

  function registerServiceWorkerAndGetToken() {
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/firebase-messaging-sw.js')
        .then(registration => {
          console.log('Service Worker registered with scope:', registration.scope);

          messaging.getToken({
            vapidKey:  "{{ env('vapidKey') }}",
            serviceWorkerRegistration: registration
          }).then(token => {
            console.log('FCM Token:', token);
            sendTokenToServer(token);
          }).catch(err => {
            console.error('Error getting token:', err);
          });
        })
        .catch(err => {
          console.error('Service Worker registration failed:', err);
        });
    } else {
      console.warn('Service workers are not supported in this browser.');
    }
  }

  initFirebaseMessagingRegistration();

messaging.onMessage(payload => {
  console.log('FCM payload:', payload); // ADD THIS LINE

  // Check if payload.notification exists
  if (payload.notification) {
    const { title, body } = payload.notification;
    const { cli, duration } = payload.data || {};

    if (Notification.permission === 'granted') {
      console.log('granted');
      const notification = new Notification(title || 'New VMN Call Received', {
        body: body || `CLI: ${cli}, Duration: ${duration}s`,
        icon: '/logo.png'
      });

      notification.onclick = event => {
        event.preventDefault();
        window.focus();
      };
    } else {
      console.warn('Notification permission not granted.');
    }
  } else {
    console.warn('No payload.notification received.');
  }
});


  
</script>

<!-- <script>
  const firebaseConfig = {
    apiKey: "AIzaSyDTPQJM6INqNY0qNw5p4N8MOjf4kr-lXi8",
    authDomain: "test-872b3.firebaseapp.com",
    projectId: "test-872b3",
    storageBucket: "test-872b3.appspot.com",
    messagingSenderId: "509693146027",
    appId: "1:509693146027:web:a786b1fb7a569b2aed58ee",
    measurementId: "G-TX9YBYZV5Y"
  };

  firebase.initializeApp(firebaseConfig);
  const messaging = firebase.messaging();

  function sendTokenToServer(token) {
    fetch('/save-fcm-token', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify({ token })
    })
    .then(res => res.json())
    .then(data => console.log('Token saved:', data))
    .catch(err => console.error('Error saving token:', err));
  }

  function initFirebaseMessagingRegistration() {
    if (!('Notification' in window)) {
      console.warn('This browser does not support notifications.');
      return;
    }

    if (Notification.permission === 'granted') {
      registerServiceWorkerAndGetToken();
    } else if (Notification.permission !== 'denied') {
      Notification.requestPermission().then(permission => {
        console.log('Notification permission:', permission);
        if (permission === 'granted') {
          registerServiceWorkerAndGetToken();
        } else {
          console.warn('Notification permission not granted');
        }
      });
    }
  }

  function registerServiceWorkerAndGetToken() {
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/firebase-messaging-sw.js')
        .then(registration => {
          console.log('Service Worker registered with scope:', registration.scope);

          messaging.getToken({
            vapidKey: 'BBmZ4PIVumuQmN_jL6JTWIjLMLAMonhb7vR8x78nQ9Ztt2r9hUm2rI7hJ6gLAxQqvEP0l8_jS0B7Z1l4t0bK8Us',
            serviceWorkerRegistration: registration
          }).then(token => {
            console.log('FCM Token:', token);
            sendTokenToServer(token);
          }).catch(err => {
            console.error('Error getting token:', err);
          });
        })
        .catch(err => {
          console.error('Service Worker registration failed:', err);
        });
    } else {
      console.warn('Service workers are not supported in this browser.');
    }
  }

  initFirebaseMessagingRegistration();

messaging.onMessage(payload => {
  console.log('FCM payload:', payload); // ADD THIS LINE

  // Check if payload.notification exists
  if (payload.notification) {
    const { title, body } = payload.notification;
    const { cli, duration } = payload.data || {};

    if (Notification.permission === 'granted') {
      console.log('granted');
      const notification = new Notification(title || 'New VMN Call Received', {
        body: body || `CLI: ${cli}, Duration: ${duration}s`,
        icon: '/logo.png'
      });

      notification.onclick = event => {
        event.preventDefault();
        window.focus();
      };
    } else {
      console.warn('Notification permission not granted.');
    }
  } else {
    console.warn('No payload.notification received.');
  }
});


  
</script> -->

</body>
</html>
