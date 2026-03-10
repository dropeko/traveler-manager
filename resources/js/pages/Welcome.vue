<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

type Feature = {
    title: string;
    description: string;
    icon: 'shield' | 'bolt' | 'layers' | 'route';
};

function iconPath(name: Feature['icon']) {
    switch (name) {
        case 'route':
            return 'M6 4h8a4 4 0 1 1 0 8H9a3 3 0 0 0 0 6h9';
        case 'bolt':
            return 'M13 2 3 14h7l-1 8 12-14h-7l1-6Z';
        case 'layers':
            return 'm12 2 9 5-9 5-9-5 9-5Zm9 10-9 5-9-5';
        case 'shield':
            return 'M12 2 20 6v6c0 5-3.5 9.5-8 10-4.5-.5-8-5-8-10V6l8-4Z';
    }
}
</script>

<template>
    <Head title="Traveler Manager — Bem-vindo">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-[#050A16] text-slate-100">
        <div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
            <div
                class="absolute -top-24 left-1/2 h-[520px] w-[520px] -translate-x-1/2 rounded-full blur-3xl"
                style="background: radial-gradient(circle at 30% 30%, rgba(96, 165, 250, 0.22), transparent 60%)"
            />
            <div
                class="absolute -bottom-28 right-[-140px] h-[520px] w-[520px] rounded-full blur-3xl"
                style="background: radial-gradient(circle at 30% 30%, rgba(30, 58, 138, 0.32), transparent 60%)"
            />
            <div class="absolute inset-0 bg-[linear-gradient(to_bottom,rgba(2,6,23,0.0),rgba(2,6,23,0.75))]" />
        </div>

        <div class="relative mx-auto flex max-w-6xl flex-col gap-10 px-6 py-10 lg:py-16">
            <header class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div
                        class="grid h-10 w-10 place-items-center rounded-xl border border-slate-800 bg-slate-950/60 shadow-sm"
                    >
                        <span class="text-sm font-semibold tracking-tight text-sky-300">TM</span>
                    </div>

                    <div class="leading-tight">
                        <p class="text-sm font-semibold text-slate-100">Traveler Manager</p>
                        <p class="text-xs text-slate-400">Gestão de solicitações de viagem</p>
                    </div>
                </div>

                <nav class="flex items-center gap-2">
                    <a
                        href="/"
                        class="rounded-lg px-3 py-2 text-sm text-slate-300 transition hover:bg-slate-900/50 hover:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                    >
                        Início
                    </a>
                    <a
                        href="/phpmyadmin"
                        class="rounded-lg px-3 py-2 text-sm text-slate-300 transition hover:bg-slate-900/50 hover:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                    >
                        Admin DB
                    </a>
                </nav>
            </header>

            <section class="flex flex-1 items-center justify-center py-10">
                <div class="w-full max-w-2xl text-center">
                    <h1 class="mt-5 text-3xl font-semibold tracking-tight text-slate-50 sm:text-4xl lg:text-5xl">
                        Bem-vindo ao <span class="text-sky-300">Traveler Manager</span>
                    </h1>

                    <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-slate-300">
                        Uma plataforma para criar, acompanhar e administrar solicitações de viagem com clareza,
                        consistência e foco em uma experiência simples — com integração direta à API.
                    </p>

                    <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row sm:items-center">
                        <Link
                            href="/login"
                            class="inline-flex items-center justify-center rounded-xl bg-sky-500 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-sm transition hover:bg-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-300/70"
                        >
                            Entrar
                        </Link>
                    </div>
                </div>
            </section>
            <footer class="border-t border-slate-900/70 pt-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">
                        Traveler Manager • UI em dark mode com acentos em azul claro/azul marinho.
                    </p>
                    <p class="text-xs text-slate-500">
                        <span class="text-slate-300">Endpoint:</span> <code>/api/v1</code>
                    </p>
                </div>
            </footer>
        </div>
    </div>
</template>