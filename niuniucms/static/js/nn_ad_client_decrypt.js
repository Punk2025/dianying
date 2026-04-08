/**
 * 广告密文图统一解密入口：
 * fetch(arrayBuffer) -> AES-256-CBC(IV16 + 密文, PKCS7) -> blob:/data: -> img.src
 */
(function () {
    var PLACEHOLDER = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    var keyB64 = window.__NN_AD_KEY_B64 || '';
    var cfg = window.__NN_AD_CLIENT_CFG || {};
    var urlMode = cfg.urlMode === 'data' ? 'data' : 'blob';
    var cachePromise = new Map();
    var cacheResolved = new Map();
    var activeBlobUrls = [];

    function mimeFromBytes(u8) {
        if (u8.length >= 3 && u8[0] === 0xff && u8[1] === 0xd8 && u8[2] === 0xff) return 'image/jpeg';
        if (u8.length >= 8 && u8[0] === 0x89 && u8[1] === 0x50 && u8[2] === 0x4e && u8[3] === 0x47) return 'image/png';
        if (u8.length >= 6 && u8[0] === 0x47 && u8[1] === 0x49 && u8[2] === 0x46) return 'image/gif';
        if (u8.length >= 12 && u8[0] === 0x52 && u8[1] === 0x49 && u8[2] === 0x46 && u8[3] === 0x46 && u8[8] === 0x57 && u8[9] === 0x45 && u8[10] === 0x42 && u8[11] === 0x50) return 'image/webp';
        return 'image/png';
    }

    function b64ToBytes(b64) {
        var bin = atob(b64);
        var out = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) out[i] = bin.charCodeAt(i);
        return out;
    }

    function bytesToDataUrl(u8, mime) {
        var bin = '';
        var chunk = 0x8000;
        for (var i = 0; i < u8.length; i += chunk) {
            bin += String.fromCharCode.apply(null, u8.subarray(i, i + chunk));
        }
        return 'data:' + mime + ';base64,' + btoa(bin);
    }

    function decryptCipher(buf) {
        if (!window.crypto || !crypto.subtle) return Promise.reject(new Error('no subtle'));
        var raw = b64ToBytes(keyB64);
        if (raw.length !== 32) return Promise.reject(new Error('bad key'));
        if (buf.length < 17) return Promise.reject(new Error('cipher short'));
        var iv = buf.slice(0, 16);
        var ct = buf.slice(16);
        return crypto.subtle.importKey('raw', raw, { name: 'AES-CBC', length: 256 }, false, ['decrypt'])
            .then(function (key) { return crypto.subtle.decrypt({ name: 'AES-CBC', iv: iv }, key, ct); })
            .then(function (plain) {
                var u8 = new Uint8Array(plain);
                return { bytes: u8, mime: mimeFromBytes(u8) };
            });
    }

    function toDisplayUrl(result) {
        if (urlMode === 'data') {
            return bytesToDataUrl(result.bytes, result.mime);
        }
        if (URL && typeof URL.createObjectURL === 'function') {
            var blobUrl = URL.createObjectURL(new Blob([result.bytes], { type: result.mime }));
            activeBlobUrls.push(blobUrl);
            return blobUrl;
        }
        return bytesToDataUrl(result.bytes, result.mime);
    }

    function resolveImageUrl(cipherUrl) {
        if (!cipherUrl || !keyB64) return Promise.resolve('');
        if (cacheResolved.has(cipherUrl)) return Promise.resolve(cacheResolved.get(cipherUrl));
        if (cachePromise.has(cipherUrl)) return cachePromise.get(cipherUrl);
        var p = fetch(cipherUrl, { credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) throw new Error('fetch fail');
                return r.arrayBuffer();
            })
            .then(function (ab) { return decryptCipher(new Uint8Array(ab)); })
            .then(function (res) {
                var displayUrl = toDisplayUrl(res);
                cacheResolved.set(cipherUrl, displayUrl);
                return displayUrl;
            })
            .finally(function () { cachePromise.delete(cipherUrl); });
        cachePromise.set(cipherUrl, p);
        return p;
    }

    function applyToImg(img) {
        var cipherUrl = img.getAttribute('data-nn-ad-cipher');
        var fallbackUrl = img.getAttribute('data-nn-ad-fallback') || '';
        if (!cipherUrl || img.getAttribute('data-nn-decrypting') === '1') return;
        img.setAttribute('data-nn-decrypting', '1');
        resolveImageUrl(cipherUrl)
            .then(function (displayUrl) {
                img.src = displayUrl || PLACEHOLDER;
            })
            .catch(function () {
                img.src = fallbackUrl || PLACEHOLDER;
            })
            .finally(function () {
                img.removeAttribute('data-nn-decrypting');
            });
    }

    function boot() {
        document.querySelectorAll('img.nn-ad-cipher-img[data-nn-ad-cipher]').forEach(applyToImg);
    }

    window.resolveImageUrl = resolveImageUrl;
    window.addEventListener('beforeunload', function () {
        activeBlobUrls.forEach(function (u) {
            try { URL.revokeObjectURL(u); } catch (e) {}
        });
        activeBlobUrls = [];
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
