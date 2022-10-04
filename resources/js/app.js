import "./bootstrap";
// import "../css/app.css";
// import "@protonemedia/laravel-splade/dist/style.css";

import { renderSpladeApp, SpladePlugin } from "@protonemedia/laravel-splade";
import { createApp } from "vue";

const el = document.getElementById("app");

createApp({
    render: renderSpladeApp({ el })
})
    .use(SpladePlugin, {
        "max_keep_alive": 10,
        "transform_anchors": false,
        "progress_bar": true
    })
    .mixin({
        methods: {
            switchMail({detail: {value}}) {
                console.log(this.$splade)
                this.$splade.visit('/temp-mail/switch?email='+value)
            }
        }
    })
    .mount(el);
