<h2>Meeting Recorder</h2>

<button onclick="startRecording()">Start</button>
<button onclick="stopRecording()">Stop</button>

<pre id="transcript"></pre>

<script>
    let recorder;

    async function startRecording() {

        const stream = await navigator.mediaDevices.getUserMedia({
            audio: true
        });

        recorder = new MediaRecorder(stream);

        recorder.ondataavailable = async e => {

            let formData = new FormData();
            formData.append("audio", e.data);

            let res = await fetch("/api/meeting/transcribe", {
                method: "POST",
                body: formData
            });

            let data = await res.json();

            document.getElementById("transcript").innerHTML += data.text + " ";

        };

        recorder.start(5000);

    }

    function stopRecording() {
        recorder.stop();
    }
</script>
