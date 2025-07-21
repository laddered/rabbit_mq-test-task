/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';

document.addEventListener('DOMContentLoaded', function() {
    var alert = document.getElementById('rabbitmq-alert');
    if (alert) {
        setTimeout(function() {
            alert.style.display = 'none';
        }, 10000);
    }

    var sendBtn = document.getElementById('send-rabbitmq-btn');
    if (sendBtn) {
        sendBtn.addEventListener('click', function(e) {
            e.preventDefault();
            sendBtn.disabled = true;
            fetch('/send-rabbitmq', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'message=' + encodeURIComponent('Message from JS!')
            })
            .then(response => response.json())
            .then(data => {
                showRabbitAlert(data.message, data.success ? 'success' : 'danger');
                sendBtn.disabled = false;
            })
            .catch(err => {
                showRabbitAlert('Ошибка отправки запроса', 'danger');
                sendBtn.disabled = false;
            });
        });
    }

    function showRabbitAlert(message, type) {
        var container = document.getElementById('rabbitmq-alert-container');
        if (!container) return;
        container.innerHTML = '<div class="alert alert-' + type + ' mt-3" role="alert">' + message + '</div>';
        setTimeout(function() {
            container.innerHTML = '';
        }, 10000);
    }
});
