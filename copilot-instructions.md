# <Nome do projeto>

Este arquivo é para agentes de código que trabalham neste repositório. Siga-o literalmente.

Checklist de comportamento inicial para o agente

- [ ] Verificar se o usuário editou arquivos antes de aplicar mudanças.
- [ ] Propor a menor mudança possível que resolva o problema (exceto ao escrever testes).
- [ ] Garantir cobertura de testes mínima quando modificar lógica crítica.

## Contexto do projeto

- **API Sentinel**: plataforma SaaS de governança de APIs que centraliza o catálogo, valida contratos OpenAPI, detecta breaking changes, aplica políticas de versionamento e integra-se a pipelines CI/CD, utilizando IA como apoio à análise semântica e à geração de recomendações técnicas.
- **Atue como cofundador.** Priorize entrega de valor ao usuário e velocidade, sem comprometer manutenção básica.

## Não negociáveis

- **Não sobrescrever edições do usuário.** O usuário pode ter modificado código entre mensagens; se algo mudou, entenda por quê e construa sobre a nova versão.
- **Mantenha mudanças simples.** Implemente a menor alteração que resolve o problema (salvo ao escrever/atualizar testes).
- **Corrija a causa raiz.** Ao debugar, reúna informações suficientes e trate a origem do erro, não apenas sintomas.

## Arquitetura e estrutura (Laravel)

- Prefira Actions pequenas, com nomes verbo-centrados. Evite classes genéricas "Service/Manager/Handler".
- Controllers finos; prefira single-action controllers quando fizer sentido.
- Evite eventos desnecessários que espalham o fluxo entre muitos arquivos.
- Jobs devem ser finos e idempotentes; delegue lógica a Actions.
- Se criar um Model, crie também factory + seeder correspondentes.

## Estilo de código (PHP)

- Documente a intenção (por que, não só o que) em código não óbvio.
- Docblocks de propósito são obrigatórios: toda classe/trait/interface/enum em `app/` deve ter um PHPDoc no topo explicando:
  - por que o arquivo existe,
  - por que a lógica foi extraída ali (em vez de inline),
  - o contrato esperado pelos chamadores quando não for óbvio.
- Importe namespaces explicitamente; não dependa de imports implícitos.
- Evite nomes ambíguos; nada de variáveis de uma letra a menos que extremamente local e óbvio.
- Prefira guard clauses a aninhamentos profundos.
- Não deixe helpers de debug (`dd()`, `dump()`, etc.) em commits.
- Não usar `final` por padrão.
- Nunca use `@` (supressão de erros); se for necessário, documente por quê e prefira alternativas explícitas.
- Métodos/propriedades não públicos devem ser `protected` por padrão, salvo necessidade clara.

## Convenções Laravel & limites de dependência

- Faça as coisas "do jeito Laravel" quando apropriado: helpers, Collections, Facades e atributos.
- Preferir Facades/Real-Time Facades ou `app()` em vez de injetar dependências via construtor em camadas altas (convenção do time).
- Não chamar `env()` fora dos arquivos de `config`.
- Prefira rotas nomeadas (`route()`) ao invés de URLs hardcoded.
- Use helpers quando houver alternativa (ex.: `session()` em vez de `Session::get()`).
- Evitar consultas SQL cruas; se inevitável, parametrize e documente a razão.

## Dados & migrations

- Migrations devem ser reversíveis quando possível.
- Nunca editar migrations antigas após merge; crie uma nova migration.

## Frontend (Blade + Tailwind + Alpine)

- HTML limpo, válido, semântico e acessível.
- Fechar tags inline (`<meta />`, `<img />`, `<br />`, ...).
- Preferir landmarks (`header`, `nav`, `main`, `footer`) em vez de wrappers genéricos.
- Manter outlines de foco visíveis e intencionais.
- Todo input precisa de `<label>` (associado por `for` + `id`) salvo justificativa clara.
- Ícones decorativos: `aria-hidden="true"`; ícones informativos devem ter nome acessível.

### Styling (Tailwind v4)

- Preferir utilitários Tailwind sobre CSS customizado.
- Se precisar de CSS customizado, mantenha mínimo e documente por quê.
- Extrair padrões de UI repetidos em componentes Blade (não copiar/colar longas classes).

### Interatividade (Alpine.js em Blade)

- Código Alpine fica no próprio componente Blade.
- Use `x-cloak` para evitar flashes durante inicialização.
- Manter estado pequeno e local; evite estados globais escondidos.
- Sincronize atributos ARIA com o estado (ex.: `aria-expanded`).

### Convenções de legibilidade (Blade)

- Quando um elemento tem muitos atributos, coloque um por linha.
- Blocos de comentário no topo do arquivo Blade usam:
  - `{{--` numa linha só,
  - Uma frase capitalizada terminando em ponto,
  - `--}}` numa linha só.

## Testes (Pest)

- Arquivos de teste devem espelhar a estrutura de `app/` 1:1 quando possível.
- Se não houver arquivo correspondente em `app/`, só então coloque testes em `./tests/Feature` com justificativa clara.
- Evite hosts/URLs hardcoded; prefira `route()` / `url()`.
- Prefira fakes estritos a mocks permissivos.
- Testes devem ser paralelizáveis: evite caminhos fixos de arquivo compartilhados e limpe artefatos criados.
- Importe funções globais do Pest quando necessário (ex.: `use function Pest\Laravel\actingAs;`).
- Evite `$this` em testes Pest; use as funções globais.
- Use Real-Time Facades para mockar resoluções do container.

## Ferramentas / definição de pronto

- Formatar: `php vendor/bin/pint --parallel`
- Testes: `php vendor/bin/pest --parallel` (use `--filter` ao iterar)
- Sanity: nenhum helper de debug deixado; migrations reversíveis; UI acessível; mudanças mínimas.

## Comportamento padrão de revisão (sempre que tocar código)

- Existence check: para cada arquivo em `app/` criado ou editado, confirme se sua existência é justificada. Se redundante/unused/over-abstracted, prefira deletar/mesclar/mover e atualizar rotas/usos/testes.
- Logic check: dentro de arquivos mantidos, remova/simplifique código não justificado (ramos mortos, opções não usadas, indireção desnecessária).
- Test alignment: mantenha testes espelhando `app/` 1:1 quando possível; atualize ou delete testes juntamente com mudanças no código.


---