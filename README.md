# API Sentinel

API Sentinel é uma plataforma SaaS de governança de APIs que centraliza o catálogo de APIs, valida contratos OpenAPI, detecta breaking changes, aplica políticas de versionamento e integra-se a pipelines CI/CD, utilizando IA como apoio à análise semântica e à geração de recomendações técnicas.

O objetivo do projeto é apoiar a evolução controlada de APIs em ambientes distribuídos, reduzindo falhas, retrabalho e impactos negativos em consumidores.

## 🎯 Objetivos do Projeto

- Centralizar o catálogo de APIs e seus contratos
- Validar automaticamente contratos OpenAPI
- Detectar mudanças incompatíveis entre versões (breaking changes)
- Aplicar políticas de versionamento (ex.: SemVer)
- Integrar governança ao fluxo de CI/CD
- Utilizar IA como suporte à análise de qualidade e consistência semântica
- Fornecer métricas de maturidade e governança de APIs

## 🧩 Escopo Inicial (MVP)

- Cadastro e catalogação de APIs
- Importação de contratos OpenAPI (YAML/JSON)
- Versionamento de contratos
- Diff estrutural entre versões
- Identificação de breaking changes
- Relatório de validação de contrato
- Integração básica com pipeline CI (GitHub Actions)

## 🧠 Uso de Inteligência Artificial

A IA é utilizada como camada de apoio à decisão, não como mecanismo autônomo. Entre os usos previstos:

- Análise semântica de nomes de endpoints e campos
- Identificação de inconsistências conceituais
- Sugestão de melhorias de design de APIs
- Geração automática de changelogs técnicos

> Observação: decisões finais devem sempre ser validadas por um engenheiro humano. Mantenha políticas claras sobre quando recomendações automáticas podem ou não bloquear pipelines.

## 🏗️ Arquitetura (Visão Geral)

- Backend: Laravel
- Frontend: Blade + Tailwind CSS + Alpine.js
- Banco de dados: PostgreSQL
- IA: LLMs integrados via serviço dedicado
- CI/CD: GitHub Actions
- Infraestrutura: Docker (com possibilidade de Kubernetes)
- Observabilidade: métricas e logs básicos

## 🔄 Integração com CI/CD

O projeto fornece pontos de integração para pipelines de CI, permitindo:

- Validação automática de contratos OpenAPI em pull requests
- Detecção de breaking changes antes do deploy
- Bloqueio de pipelines quando regras de governança são violadas
- Geração de relatórios automatizados

## 🧪 APIs Utilizadas para Testes

Para validação da solução o projeto utiliza:

- APIs públicas brasileiras (dados abertos)
- APIs públicas amplamente adotadas no mercado
- APIs simuladas desenvolvidas para testes controlados de versionamento e breaking changes

## 📚 Contexto Acadêmico

Este projeto é desenvolvido como trabalho final de pós-graduação em Desenvolvimento Web e explora conceitos de:

- Engenharia de Software
- Arquitetura de Sistemas Distribuídos
- Governança de APIs
- DevOps
- Uso responsável de IA em processos de engenharia

## Começando (guia rápido)

Esses passos são um ponto de partida. Ajuste conforme a estrutura real do repositório.

1. Clonar o repositório

```bash
git clone <repo-url>
cd <repo-dir>
```

2. Usando Docker (recomendado para desenvolvimento)

- Levantar containers (exemplo genérico):

```bash
# Exemplo hipotético - ajuste conforme docker-compose do projeto
docker compose up -d --build
```

3. Configuração manual (sem Docker)

- Instalar dependências PHP/Composer

```bash
composer install
cp .env.example .env
# configurar .env (DB, keys, etc.)
php artisan key:generate
php artisan migrate --seed
npm install && npm run dev
```

4. Executar testes

```bash
# PHPUnit / Pest
vendor/bin/pest --parallel
```

## Contribuindo

- Abra uma issue descrevendo o problema ou feature desejada.
- Crie branches com padrão `feature/<descricao>` ou `fix/<descricao>`.
- Inclua testes que cubram as mudanças importantes.
- Siga as convenções de estilo e segurança do projeto.

## Roadmap / Próximos passos

- Integração avançada com pipelines (políticas configuráveis)
- Dashboards de métricas de maturidade e impacto
- Suporte a múltiplos provedores de LLM e políticas de fallback
- Pacotes/SDKs para integração com consumidores de APIs

## Licença

Adicione aqui a licença do projeto (ex.: MIT) ou outro texto conforme necessidade.

---

Se quiser, eu atualizo os comandos de setup com detalhes reais do seu repositório (por exemplo `docker-compose.yml`, `.env.example`, e scripts npm) — só precisa me confirmar que esses arquivos existem ou permitir que eu os crie com um template.
