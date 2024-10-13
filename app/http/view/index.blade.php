<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/favicon.ico">
    <title>Nos for ripple demo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #8674eb, #ACB6E5);
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            min-height: 100vh;
            padding: 12px;
        }

        h1 {
            margin-bottom: 12px;
            color: #333;
            font-size: 2.5rem;
        }

        h2 {
            margin-bottom: 12px;
            color: #555;
        }

        h4 {
            margin-bottom: 12px;
            color: #555;
        }

        .container {
            display: flex;
            justify-content: space-between;
            max-width: 1200px;
            width: 100%;
            gap: 12px;
            margin-top: 24px;
        }

        .left, .right {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .section {
            background-color: white;
            padding: 12px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .section:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        h2 {
            color: #333;
            font-size: 1.5rem;
        }

        button {
            background-color: #74ebd5;
            border: none;
            padding: 10px 12px;
            color: white;
            cursor: pointer;
            border-radius: 8px;
            font-size: 1rem;
            transition: background-color 0.3s, box-shadow 0.3s;
            margin-top: 12px;
        }

        button:hover {
            background-color: #5ac4b3;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        input, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }

        textarea {
            min-height: 80px;
        }

        .output {
            margin-top: 12px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            white-space: pre-wrap;
            font-size: 0.9rem;
            color: #555;
        }

        .output:empty {
            display: none;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
        }

        .mac-window {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .mac-window-header {
            background-color: #f5f5f5;
            padding: 8px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #ddd;
        }

        .mac-window-header .button {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .button.red {
            background-color: #ff5f57;
        }

        .button.yellow {
            background-color: #ffbd2e;
        }

        .button.green {
            background-color: #28c840;
        }

        pre code {
            display: block;
            padding: 16px;
            font-size: 14px;
            background-color: white;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }
    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            document.querySelectorAll('pre code').forEach((el) => {
                hljs.highlightElement(el);
            });
        });
    </script>
</head>
<body>
<h1>Nos application for ripple</h1>
<div class="container">
    <h4>
        This is based on
        <a href="https://github.com/cloudtay/ripple" target="_blank">ripple</a>
        Scaffolding project Nos created by the engine for rapid development of web applications. If you want to know
        more
        information, welcome to visit the project address
        <a href="https://github.com/cloudtay/nos" target="_blank">source</a>
    </h4>
</div>
<div class="container">
    <div class="left">
        <div class="section" id="send-section">
            <h2>Broadcast a Message (POST)</h2>
            <h4>Broadcast a message to the WebSocket server</h4>
            <div class="mac-window">
                <div class="mac-window-header">
                    <span class="button red"></span>
                    <span class="button yellow"></span>
                    <span class="button green"></span>
                    <span style="flex: 1;text-align: right;">Source code</span>
                </div>
                <pre><code>$http = Kernel::import('http');
Route::define(Method::POST, '/broadcast', static function (Request $request) use ($http) {
    if ($message = $request->POST['message'] ?? null) {
        $command = Command::make('message', [$message]);
        $http->commandToWorker($command, 'ws-server');
        $request->respondJson(
            \json_encode(['message' => 'Message sent!'])
        );
        return;
    }

    $request->respondJson(
        \json_encode(
            ['error' => 'Message is required!']
        )
    );
});</code></pre>
            </div>

            <input type="text" id="message" placeholder="Type your message here">
            <button onclick="sendMessage()">Broadcast</button>
            <div class="output" id="post-response"></div>
        </div>

        <div class="section" id="sse-section">
            <h2>Server-Sent Events (SSE)</h2>
            <h4>Stream data from the server to the client</h4>

            <div class="mac-window">
                <div class="mac-window-header">
                    <span class="button red"></span>
                    <span class="button yellow"></span>
                    <span class="button green"></span>
                    <span style="flex: 1;text-align: right;">Source code</span>
                </div>
                <pre><code>Route::define(Method::GET, '/sse', static function (Request $request) {
    $content = static function () {
        for ($i = 0; $i < 10; $i++) {
            yield Chunk::event('message', 'Hello, World!', \strval($i));
            \Co\sleep(1);
        }

        return false;
    };

    $request->respond($content(), [
        'Content-Type'  => 'text/event-stream',
        'Cache-Control' => 'no-cache',
    ]);
});</code></pre>
            </div>
            <button onclick="startSSE()">Start SSE</button>
            <div class="output" id="sse-output">No events yet...</div>
        </div>
    </div>

    <div class="right">
        <div class="section" id="download-section">
            <h2>File Download Test</h2>
            <h4>Download a file from the server</h4>
            <button onclick="startDownload()">Download Test</button>
            <button onclick="function startDownloadSource() {
                const link = 'https://github.com/cloudtay/nos/archive/refs/tags/dev-main.zip';
                window.open(link, '_blank');
            }
            startDownloadSource();">Download Source
            </button>
            <p id="download-status"></p>
        </div>

        <div class="section" id="websocket-section">
            <h2>WebSocket Manager</h2>
            <h4 id="ws-online">Online [undefined]</h4>
            <div>
                <button onclick="connectWebSocket()">Connect</button>
                <button onclick="disconnectWebSocket()">Disconnect</button>
                <button onclick="clearWebSocketLogs()">clear</button>
            </div>
            <textarea id="ws-message" placeholder="Enter message to send"></textarea>
            <button onclick="sendWebSocketMessage()">Broadcast via WebSocket</button>
            <div class="output" id="ws-output">WebSocket output...</div>
        </div>
    </div>
</div>

<script>
    let webSocketAddress;
    if (window.location.protocol === 'https:') {
        webSocketAddress = `wss://${window.location.host}/wss`;
    } else {
        webSocketAddress = `ws://${window.location.hostname}:8001`;
    }

    function startDownload() {
        const link = document.createElement('a');
        link.href = '/download';
        link.download = 'file.txt';
        link.click();
        document.getElementById('download-status').textContent = 'Download started!';
    }

    function sendMessage() {
        const message = document.getElementById('message').value;
        fetch('/broadcast', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({message: message})
        })
            .then(response => response.json())
            .then(data => {
                document.getElementById('post-response').textContent = 'Server response: ' + JSON.stringify(data);
            })
            .catch(error => {
                document.getElementById('post-response').textContent = 'Error: ' + error;
            });
    }

    function startSSE() {
        const sseOutput = document.getElementById('sse-output');
        const eventSource = new EventSource('/sse');
        eventSource.onmessage = function (event) {
            sseOutput.textContent += '\n' + event.data;
        };
        eventSource.onerror = function () {
            sseOutput.textContent += '\nClose connecting to stream.';
            eventSource.close();
        };
    }

    let ws;

    function connectWebSocket() {
        if (ws) {
            ws.close();
        }
        let timer;
        ws = new WebSocket(webSocketAddress);
        ws.onopen = function () {
            document.getElementById('ws-output').textContent = 'WebSocket connected!';
            timer = setInterval(() => {
                ws.send('ping');
            }, 10000);
        };
        ws.onmessage = function (event) {
            try {
                const parse = JSON.parse(event.data);
                document.getElementById('ws-online').textContent = `Online [${parse.data.count}]`;
            } catch (e) {
                document.getElementById('ws-output').textContent += '\nReceived: ' + event.data;
                return;
            }
        };
        ws.onclose = function () {
            document.getElementById('ws-output').textContent += '\nWebSocket disconnected.';
            document.getElementById('ws-online').textContent = 'Online [undefined]';

            clearInterval(timer);
            clearWebSocketLogs();
        };
        ws.onerror = function (error) {
            document.getElementById('ws-output').textContent += '\nWebSocket error: ' + error;
        };
    }

    function disconnectWebSocket() {
        if (ws) {
            ws.close();
        }
    }

    function sendWebSocketMessage() {
        const message = document.getElementById('ws-message').value;
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(message);
            document.getElementById('ws-output').textContent += '\nSent: ' + message;
        } else {
            document.getElementById('ws-output').textContent += '\nWebSocket is not connected.';
        }
    }

    function clearWebSocketLogs() {
        document.getElementById('ws-output').textContent = 'WebSocket output...';
    }
</script>
</body>
</html>
