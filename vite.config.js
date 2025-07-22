import { sveltekit } from "@sveltejs/kit/vite";
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        sveltekit(),
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        tailwindcss(),
    ],
    define: {
        "import.meta.env.VITE_API_BASE_URL": JSON.stringify(
            process.env.API_BASE_URL || "http://192.168.1.34:8001/api"
        ),
    },
});
