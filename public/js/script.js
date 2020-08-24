document.addEventListener('DOMContentLoaded', () => {
    const token = document.getElementById('token').value;

    const pictureContainer = document.getElementById('pictureContainer');
    const expiredConteiner = document.getElementById('expiredConteiner');

    const inAlbumTitle = document.getElementById('inAlbumTitle');
    const saveBtn = document.getElementById('saveBtn');

    if (saveBtn) {
        saveBtn.addEventListener('click', (ev) => {
            ev.preventDefault();
            const title = inAlbumTitle.value;
            fetch('/api/save', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    api_token: token,
                    albumTitle: title
                })
            }).then((resp) => {
                return resp.text();
            }).then((text) => {
                console.log(text);
                if (text === 'ok') {
                    alert('save success!');
                }
            }).catch((err) => console.error(err));
        });
    }

    if (token) {
        fetch('/api/album', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                api_token: token,
            })
        }).then((resp) => {
            return resp.json();
        }).then((json) => {
            pictureContainer.innerHTML = '';
            expiredConteiner.innerHTML = '';
            json.data.map((elm) => {
                const li = document.createElement('li');
                const img = document.createElement('img');
                img.src = elm.baseUrl;
                img.alt = elm.fileName;
                li.appendChild(img);
                pictureContainer.appendChild(li);
            });
            json.expired.map((elm) => {
                const li = document.createElement('li');
                const a = document.createElement('a');
                a.href = elm;
                a.textContent = 'get new access token';
                li.appendChild(a);
                expiredConteiner.appendChild(li);
            });
        }).catch(err => console.error(err));
    }
});