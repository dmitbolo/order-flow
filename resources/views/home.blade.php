<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Order Flow — Laravel-приложение для управления заказами и складскими остатками.">
    <title>{{ config('app.name', 'Order Flow') }}</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #090b10;
            --surface: #11151d;
            --surface-soft: #171c26;
            --line: rgba(255, 255, 255, .1);
            --text: #f4f1eb;
            --muted: #a7adba;
            --accent: #f5a524;
            --accent-soft: rgba(245, 165, 36, .12);
            --green: #5ee0a0;
            --max-width: 1160px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            background:
                radial-gradient(circle at 84% 8%, rgba(245, 165, 36, .12), transparent 28rem),
                radial-gradient(circle at 8% 42%, rgba(77, 123, 255, .09), transparent 34rem),
                var(--bg);
            color: var(--text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.6;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        a:focus-visible {
            outline: 2px solid var(--accent);
            outline-offset: 4px;
            border-radius: 4px;
        }

        .shell {
            width: min(calc(100% - 40px), var(--max-width));
            margin: 0 auto;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 84px;
            border-bottom: 1px solid var(--line);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            font-weight: 750;
            letter-spacing: .02em;
        }

        .brand-mark {
            display: grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border: 1px solid rgba(245, 165, 36, .45);
            border-radius: 10px;
            background: var(--accent-soft);
            color: var(--accent);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 14px;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 24px;
            color: var(--muted);
            font-size: 14px;
        }

        nav a {
            transition: color .2s ease;
        }

        nav a:hover {
            color: var(--text);
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
            gap: 72px;
            align-items: center;
            min-height: 620px;
            padding: 88px 0 72px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 24px;
            color: var(--muted);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .eyebrow::before {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--green);
            box-shadow: 0 0 18px rgba(94, 224, 160, .72);
            content: "";
        }

        h1 {
            max-width: 760px;
            margin: 0;
            font-size: clamp(48px, 7vw, 86px);
            font-weight: 760;
            letter-spacing: -.065em;
            line-height: .98;
        }

        h1 span {
            color: var(--accent);
        }

        .lead {
            max-width: 680px;
            margin: 30px 0 0;
            color: var(--muted);
            font-size: clamp(17px, 2vw, 20px);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 36px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 20px;
            border: 1px solid var(--line);
            border-radius: 10px;
            color: var(--text);
            font-size: 14px;
            font-weight: 700;
            transition: transform .2s ease, border-color .2s ease, background .2s ease;
        }

        .button:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, .24);
            background: rgba(255, 255, 255, .04);
        }

        .button-primary {
            border-color: var(--accent);
            background: var(--accent);
            color: #181008;
        }

        .button-primary:hover {
            border-color: #ffc462;
            background: #ffc462;
        }

        .system-card {
            position: relative;
            padding: 28px;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 20px;
            background: linear-gradient(145deg, rgba(23, 28, 38, .95), rgba(13, 16, 23, .92));
            box-shadow: 0 30px 80px rgba(0, 0, 0, .35);
        }

        .system-card::after {
            position: absolute;
            top: -90px;
            right: -80px;
            width: 190px;
            height: 190px;
            border-radius: 50%;
            background: rgba(245, 165, 36, .1);
            filter: blur(8px);
            content: "";
        }

        .card-label {
            margin-bottom: 24px;
            color: var(--muted);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 11px;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .flow {
            position: relative;
            display: grid;
            gap: 10px;
            z-index: 1;
        }

        .flow-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 16px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: rgba(255, 255, 255, .025);
        }

        .flow-index {
            display: grid;
            flex: 0 0 auto;
            width: 28px;
            height: 28px;
            place-items: center;
            border-radius: 8px;
            background: var(--accent-soft);
            color: var(--accent);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 11px;
        }

        .flow-title {
            font-size: 14px;
            font-weight: 700;
        }

        .flow-detail {
            margin-left: auto;
            color: var(--muted);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 11px;
        }

        section {
            padding: 112px 0;
        }

        .section-heading {
            display: grid;
            grid-template-columns: .7fr 1.3fr;
            gap: 48px;
            margin-bottom: 48px;
        }

        .kicker {
            color: var(--accent);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        h2 {
            max-width: 700px;
            margin: 0;
            font-size: clamp(32px, 5vw, 52px);
            letter-spacing: -.045em;
            line-height: 1.08;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .feature {
            min-height: 260px;
            padding: 28px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: rgba(17, 21, 29, .76);
        }

        .feature-number {
            color: var(--accent);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
        }

        .feature h3 {
            margin: 52px 0 12px;
            font-size: 20px;
            letter-spacing: -.025em;
        }

        .feature p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        .surfaces {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .surface-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 94px;
            padding: 22px 24px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: rgba(17, 21, 29, .68);
            transition: transform .2s ease, border-color .2s ease, background .2s ease;
        }

        .surface-link:hover {
            transform: translateY(-2px);
            border-color: rgba(245, 165, 36, .45);
            background: var(--accent-soft);
        }

        .surface-title {
            font-weight: 730;
        }

        .surface-path {
            margin-top: 3px;
            color: var(--muted);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
        }

        .surface-arrow {
            color: var(--accent);
            font-size: 21px;
        }

        footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 120px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 13px;
        }

        footer a:hover {
            color: var(--text);
        }

        @media (max-width: 900px) {
            .hero {
                grid-template-columns: 1fr;
                gap: 52px;
                padding-top: 72px;
            }

            .system-card {
                max-width: 600px;
            }

            .section-heading {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }

            .feature {
                min-height: auto;
            }

            .feature h3 {
                margin-top: 28px;
            }
        }

        @media (max-width: 640px) {
            .shell {
                width: min(calc(100% - 28px), var(--max-width));
            }

            header {
                min-height: 72px;
            }

            nav a:not(:last-child) {
                display: none;
            }

            .hero {
                min-height: auto;
                padding: 64px 0;
            }

            h1 {
                font-size: clamp(44px, 15vw, 66px);
            }

            .flow-detail {
                display: none;
            }

            section {
                padding: 80px 0;
            }

            .surfaces {
                grid-template-columns: 1fr;
            }

            footer {
                align-items: flex-start;
                flex-direction: column;
                justify-content: center;
                gap: 6px;
            }
        }
    </style>
</head>
<body>
<div class="shell">
    <header>
        <a class="brand" href="{{ url('/') }}" aria-label="Order Flow — главная">
            <span class="brand-mark">OF</span>
            <span>Order Flow</span>
        </a>
        <nav aria-label="Основная навигация">
            <a href="{{ url('/api/documentation') }}">API</a>
            <a href="{{ url('/admin') }}">Админка</a>
            <a href="https://github.com/dmitbolo/order-flow" target="_blank" rel="noreferrer">GitHub ↗</a>
        </nav>
    </header>

    <main>
        <div class="hero">
            <div>
                <div class="eyebrow">Laravel 13 · REST API · PostgreSQL</div>
                <h1>Заказы и склад <span>без расхождений.</span></h1>
                <p class="lead">
                    Order Flow резервирует товар в транзакции, сохраняет историю движений
                    и отправляет уведомления через очереди.
                </p>
                <div class="actions">
                    <a class="button button-primary" href="{{ url('/api/documentation') }}">Документация API</a>
                    <a class="button" href="{{ url('/admin') }}">Открыть админ-панель</a>
                </div>
            </div>

            <aside class="system-card" aria-label="Поток создания заказа">
                <div class="card-label">Создание заказа</div>
                <div class="flow">
                    <div class="flow-row">
                        <span class="flow-index">01</span>
                        <span class="flow-title">Проверка запроса</span>
                        <span class="flow-detail">Sanctum</span>
                    </div>
                    <div class="flow-row">
                        <span class="flow-index">02</span>
                        <span class="flow-title">Блокировка остатков</span>
                        <span class="flow-detail">FOR UPDATE</span>
                    </div>
                    <div class="flow-row">
                        <span class="flow-index">03</span>
                        <span class="flow-title">Заказ и журнал</span>
                        <span class="flow-detail">transaction</span>
                    </div>
                    <div class="flow-row">
                        <span class="flow-index">04</span>
                        <span class="flow-title">Отправка уведомлений</span>
                        <span class="flow-detail">after commit</span>
                    </div>
                </div>
            </aside>
        </div>

        <section>
            <div class="section-heading">
                <div class="kicker">Надёжность</div>
                <h2>Данные остаются согласованными при каждом изменении.</h2>
            </div>

            <div class="feature-grid">
                <article class="feature">
                    <div class="feature-number">01</div>
                    <h3>Конкурентные заказы</h3>
                    <p>Остатки блокируются на время транзакции, а база данных не позволяет сохранить отрицательное значение.</p>
                </article>
                <article class="feature">
                    <div class="feature-number">02</div>
                    <h3>Понятная история</h3>
                    <p>Для каждого списания, возврата и исправления видно количество до и после операции, заказ и автора изменения.</p>
                </article>
                <article class="feature">
                    <div class="feature-number">03</div>
                    <h3>Безопасные уведомления</h3>
                    <p>Почта и проверка низких остатков запускаются после commit и не мешают сохранить сам заказ.</p>
                </article>
            </div>
        </section>

        <section id="interfaces">
            <div class="section-heading">
                <div class="kicker">Интерфейсы</div>
                <h2>Документация, админ-панель и служебные экраны.</h2>
            </div>

            <div class="surfaces">
                <a class="surface-link" href="{{ url('/api/documentation') }}">
                    <span>
                        <span class="surface-title">Документация API</span>
                        <span class="surface-path">/api/documentation</span>
                    </span>
                    <span class="surface-arrow">→</span>
                </a>
                <a class="surface-link" href="{{ url('/admin') }}">
                    <span>
                        <span class="surface-title">Панель оператора</span>
                        <span class="surface-path">/admin</span>
                    </span>
                    <span class="surface-arrow">→</span>
                </a>
                <a class="surface-link" href="{{ url('/horizon') }}">
                    <span>
                        <span class="surface-title">Очереди Horizon</span>
                        <span class="surface-path">/horizon · только для администратора</span>
                    </span>
                    <span class="surface-arrow">→</span>
                </a>
                <a class="surface-link" href="{{ url('/up') }}">
                    <span>
                        <span class="surface-title">Проверка состояния</span>
                        <span class="surface-path">/up</span>
                    </span>
                    <span class="surface-arrow">→</span>
                </a>
            </div>
        </section>
    </main>

    <footer>
        <span>Order Flow</span>
        <a href="https://github.com/dmitbolo/order-flow" target="_blank" rel="noreferrer">Код на GitHub ↗</a>
    </footer>
</div>
</body>
</html>
