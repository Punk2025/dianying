(function () {
    function byId(id) {
        return document.getElementById(id);
    }
    function adToggleType() {
        var t = byId('ad_type').value;
        var img = byId('blk_img');
        var html = byId('blk_html');
        if (t === '2') {
            img.style.display = 'none';
            html.style.display = '';
        } else {
            img.style.display = '';
            html.style.display = 'none';
        }
    }
    function adRefreshSlotSize() {
        var sel = byId('ad_slot_key');
        var opt = sel.options[sel.selectedIndex];
        var s = opt && opt.getAttribute('data-size') ? opt.getAttribute('data-size') : '';
        byId('ad_slot_size_text').textContent = s || '请选择版位';
    }
    function init() {
        var adType = byId('ad_type');
        var slotKey = byId('ad_slot_key');
        var upBtn = byId('ad_image_upload_btn');
        var fileEl = byId('ad_image_file');
        var urlEl = byId('ad_image_url');
        if (!adType || !slotKey || !upBtn || !fileEl || !urlEl) {
            return;
        }
        adType.addEventListener('change', adToggleType);
        adToggleType();
        slotKey.addEventListener('change', adRefreshSlotSize);
        adRefreshSlotSize();
        upBtn.addEventListener('click', function () {
            fileEl.click();
        });
        fileEl.addEventListener('change', function () {
            if (!fileEl.files || !fileEl.files.length) {
                return;
            }
            var fd = new FormData();
            fd.append('ad_image', fileEl.files[0]);
            var api = upBtn.getAttribute('data-upload-api') || '';
            fetch(api, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) {
                    return r.text();
                })
                .then(function (txt) {
                    var res = null;
                    try {
                        res = JSON.parse(txt);
                    } catch (e) {}
                    if (res && res.code === 0 && res.url) {
                        urlEl.value = res.url;
                        alert(res.message || '上传成功');
                    } else {
                        alert(res && res.message ? res.message : '上传失败');
                    }
                })
                .catch(function () {
                    alert('上传失败');
                });
            fileEl.value = '';
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
