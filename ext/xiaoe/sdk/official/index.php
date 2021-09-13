<!DOCTYPE html>
<html data-scale=true>
<head>
    <meta charset=utf-8>
    <title>小e助手</title>
    <meta name=apple-mobile-web-app-capable content=yes>
    <meta name=apple-mobile-web-app-status-bar-style content=black-translucent>
    <meta name=format-detection content="telephone=no, email=no">
    <script src=//res.wx.qq.com/open/js/jweixin-1.2.0.js></script>
    <script>;(function (win) {
            var doc = win.document;
            var docEl = doc.documentElement;
            var metaEl = doc.querySelector('meta[name="viewport"]');
            var dpr = 0;
            var scale = 0;
            var tid;
            var flexible = {};
            if (metaEl) {
                console.warn('将根据已有的meta标签来设置缩放比例');
                var match = metaEl.getAttribute('content').match(/initial\-scale=([\d\.]+)/);
                if (match) {
                    scale = parseFloat(match[1]);
                    dpr = parseInt(1 / scale);
                }
            }
            if (!dpr && !scale) {
                var isIPhone = win.navigator.appVersion.match(/iphone/gi);
                var devicePixelRatio = win.devicePixelRatio;
                // if (isIPhone) {
                //     // iOS下，对于2和3的屏，用2倍的方案，其余的用1倍方案
                //     if (devicePixelRatio >= 3 && (!dpr || dpr >= 3)) {                
                //         dpr = 3;
                //     } else if (devicePixelRatio >= 2 && (!dpr || dpr >= 2)){
                //         dpr = 2;
                //     } else {
                //         dpr = 1;
                //     }
                // } else {
                //     // 其他设备下，仍旧使用1倍的方案
                //     dpr = 1;
                // }
                dpr = 1;
                scale = 1 / dpr;
            }
            docEl.setAttribute('data-dpr', dpr);
            if (!metaEl) {
                metaEl = doc.createElement('meta');
                metaEl.setAttribute('name', 'viewport');
                metaEl.setAttribute('content', 'initial-scale=' + scale + ', maximum-scale=' + scale + ', minimum-scale=' + scale + ', user-scalable=no');
                if (docEl.firstElementChild) {
                    docEl.firstElementChild.appendChild(metaEl);
                } else {
                    var wrap = doc.createElement('div');
                    wrap.appendChild(metaEl);
                    doc.write(wrap.innerHTML);
                }
            }

            function refreshRem() {
                var width = docEl && docEl.clientWidth || doc.body.clientWidth || win.innerWidth;
                var rem = width / 10;
                docEl.style.fontSize = rem + 'px';
                flexible.rem = win.rem = rem;
            }

            win.addEventListener('resize', function () {
                clearTimeout(tid);
                tid = setTimeout(refreshRem, 300);
            }, false);
            win.addEventListener('pageshow', function (e) {
                if (e.persisted) {
                    clearTimeout(tid);
                    tid = setTimeout(refreshRem, 300);
                }
            }, false);

            // if (doc.readyState === 'complete') {
            //     doc.body.style.fontSize = 12 * dpr + 'px';
            // } else {
            //     doc.addEventListener('DOMContentLoaded', function(e) {
            //         doc.body.style.fontSize = 12 * dpr + 'px';
            //     }, false);
            // }


            refreshRem();


            flexible.rem2px = function (d) {
                var val = parseFloat(d) * this.rem;
                if (typeof d === 'string' && d.match(/rem$/)) {
                    val += 'px';
                }
                return val;
            }
            flexible.px2rem = function (d) {
                var val = parseFloat(d) / this.rem;
                if (typeof d === 'string' && d.match(/px$/)) {
                    val += 'rem';
                }
                return val;
            }
            win.flexible = flexible;

        })(window);</script>
    <script>if (location.href.indexOf('?') > 0) {
            location.href = location.href.split('?')[0]
        }
        if (!window.Promise) {
            document.writeln('<script src="//as.alipayobjects.com/g/component/es6-promise/3.2.2/es6-promise.min.js"' + '>' + '<' + '/' + 'script>');
        }</script>
    <style>#__vconsole {
            display: none;
            /* display: block; */
            z-index: 10000;
        }</style>
    <style>.loader:before, .loader:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            display: block;
            width: 1em;
            height: 1em;
            border-radius: 0.5em;
            transform: translate(-50%, -50%);
        }

        .loader:before {
            animation: before 2s infinite;
        }

        .loader:after {
            animation: after 2s infinite;
        }

        @keyframes before {
            0% {
                width: 1em;
                box-shadow: 1em -0.5em rgba(225, 20, 98, 0.75), -1em 1em rgba(111, 202, 220, 0.75);
            }
            35% {
                width: 5em;
                box-shadow: 0 -1em rgba(225, 20, 98, 0.75), 0 1em rgba(111, 202, 220, 0.75);
            }
            70% {
                width: 1em;
                box-shadow: -1em -1em rgba(225, 20, 98, 0.75), 1em 1em rgba(111, 202, 220, 0.75);
            }
            100% {
                box-shadow: 1em -1em rgba(225, 20, 98, 0.75), -1em 1em rgba(111, 202, 220, 0.75);
            }
        }

        @keyframes after {
            0% {
                height: 1em;
                box-shadow: 1em 2em rgba(61, 184, 143, 0.75), -1em -2em rgba(233, 169, 32, 0.75);
            }
            35% {
                height: 5em;
                box-shadow: 1em 0 rgba(61, 184, 143, 0.75), -1em 0 rgba(233, 169, 32, 0.75);
            }
            70% {
                height: 1em;
                box-shadow: 1em -2em rgba(61, 184, 143, 0.75), -1em 2em rgba(233, 169, 32, 0.75);
            }
            100% {
                box-shadow: 1em 2em rgba(61, 184, 143, 0.75), -1em -2em rgba(233, 169, 32, 0.75);
            }
        }

        #loading {
            position: absolute;
            top: calc(50% - 4em);
            left: calc(50% - 1.5em);
            animation: opacityIn 1.5s ease-in;
        }

        #loading p {
            position: relative;
            top: 5em;
            color: #fff;
        }

        @keyframes opacityIn {
            0% {
                opacity: 0;
            }
            99% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }</style>
    <link href=<?php echo $dir ?>/css/app.f90839f.css rel=stylesheet>
</head>
<body>
<div id=root></div>
<div id=bdtts_div_id>
    <audio id=tts_autio_id autoplay=autoplay>
        <source id=tts_source_id src="" type=audio/mpeg>
        <embed id=tts_embed_id height=0 width=0 src="">
    </audio>
</div>
<script></script>
<script type=text/javascript src=<?php echo $dir ?>/assets/manifest.410e8fe.js></script>
<script type=text/javascript src=<?php echo $dir ?>/assets/vendor.570a14e.js></script>
<script type=text/javascript src=<?php echo $dir ?>/assets/app.36251bf.js></script>
</body>
</html>