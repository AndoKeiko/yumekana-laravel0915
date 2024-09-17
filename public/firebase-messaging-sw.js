// Firebase v9 互換レイヤーを使用する場合
importScripts('https://www.gstatic.com/firebasejs/9.17.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.17.1/firebase-messaging-compat.js');

// Firebase の構成オブジェクト
firebase.initializeApp({
  apiKey: "AIzaSyAYpQFk7e4uqoX8wHx16aRNMm2-07OQpCc",
  authDomain: "sotusei0913.firebaseapp.com",
  projectId: "sotusei0913",
  storageBucket: "752696545021.appspot.com",
  messagingSenderId: "752696545021",
  appId: "BBs5OrtFLZMzPmXsGtdAtAv4zIAgcLQsCk7JS_NNlVZ-ATMlCVJx-G3Yr7ezffr9SyNc_ZFrJw44_qWgZktWrCc"
});

// Firebase Messaging のインスタンスを取得
const messaging = firebase.messaging();

// バックグラウンドメッセージを受信したときの処理
messaging.onBackgroundMessage(function(payload) {
  console.log('[firebase-messaging-sw.js] 受信したバックグラウンドメッセージ: ', payload);
  // 通知のカスタマイズ
  const notificationTitle = payload.notification.title;
  const notificationOptions = {
    body: payload.notification.body,
    icon: '/firebase-logo.png' // アイコン画像のパスを指定（任意）
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});
