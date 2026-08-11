# Digital Signage — Plataforma de Publicidade em TVs

Plataforma para gerenciamento e exibição de publicidade digital em TVs instaladas em estabelecimentos parceiros.

O projeto será dividido em **dois sistemas principais**:

1. **Digital Signage Server**
   - Laravel
   - Vue 3
   - PrimeVue
   - MySQL
   - Painel administrativo
   - Portal dos clientes
   - API central consumida pelos dispositivos
   - Campanhas, mídias, telas, relatórios, usuários e permissões

2. **Digital Signage Device**
   - Node.js
   - Linux
   - Chromium em modo kiosk
   - API/serviço local
   - Sincronização com o Laravel
   - Download e cache das mídias
   - Reprodução local
   - Heartbeat
   - Logs
   - Funcionamento offline

---

# 1. Objetivo

Criar uma plataforma própria de Digital Signage / DOOH para instalar TVs em estabelecimentos com circulação ou permanência de pessoas e vender espaços publicitários para empresas.

A plataforma deverá permitir:

- administrar estabelecimentos parceiros;
- administrar TVs e dispositivos;
- cadastrar clientes anunciantes;
- criar campanhas;
- enviar vídeos e imagens;
- aprovar conteúdos;
- vincular campanhas a clientes;
- definir onde e quando cada campanha será exibida;
- acompanhar dispositivos online e offline;
- registrar exibições;
- gerar relatórios;
- permitir futuramente que o próprio cliente envie e atualize seus conteúdos;
- manter a reprodução funcionando mesmo sem internet.

---

# 2. Arquitetura Geral

```text
                         SERVIDOR CENTRAL

                Laravel + Vue 3 + PrimeVue
             ┌──────────────────────────────┐
             │                              │
             │ Dashboard                    │
             │ Usuários                     │
             │ Suporte e permissões         │
             │ Clientes                     │
             │ Campanhas                    │
             │ Mídias                       │
             │ Aprovação de conteúdo        │
             │ Estabelecimentos             │
             │ Telas                        │
             │ Dispositivos                 │
             │ Playlists                    │
             │ Monitoramento                │
             │ Relatórios                   │
             │ Auditoria                    │
             │                              │
             └───────────────┬──────────────┘
                             │
                             │ API HTTPS / JSON
                             │
═════════════════════════════╪══════════════════════════════
                             │
                             ▼

                     PC DO ESTABELECIMENTO

                      Node.js Agent
               ┌────────────────────────┐
               │ autenticação device    │
               │ sincronização          │
               │ download               │
               │ cache local            │
               │ heartbeat              │
               │ logs                   │
               │ watchdog               │
               │ eventos de reprodução  │
               │ API local              │
               └────────────┬───────────┘
                            │
                            ▼
                       Player local
                            │
                            ▼
                    Chromium --kiosk
                            │
                           HDMI
                            │
                            ▼
                            TV
```

---

# 3. Repositórios

## 3.1 Digital Signage Server

```text
digital-signage/
```

Responsável por painel administrativo, portal do cliente, API central, autenticação, autorização, banco de dados, campanhas, mídias, dispositivos, relatórios, auditoria e regras de negócio.

Stack:

```text
Laravel
Vue 3
PrimeVue
Pinia
Vue Router
Sanctum
MySQL
Vite
```

## 3.2 Digital Signage Device

```text
digital-signage-device/
```

Responsável pelo computador conectado à TV.

Stack:

```text
Node.js
Linux
Chromium
SQLite ou arquivos locais
systemd
HTML5 Video
```

---

# 4. Perfis de Usuário

## 4.1 Admin

Administrador da plataforma.

Pode:

- visualizar toda a plataforma;
- cadastrar usuários internos;
- cadastrar suportes;
- administrar permissões;
- cadastrar clientes;
- administrar estabelecimentos;
- administrar dispositivos;
- criar campanhas;
- aprovar ou reprovar mídias;
- alterar campanhas;
- visualizar relatórios;
- visualizar logs de auditoria;
- administrar configurações da plataforma.

## 4.2 Suporte

Usuário interno da plataforma com acesso controlado por permissões.

Exemplos:

```text
dashboard.view
users.view
users.create
users.update
users.disable
clients.view
clients.create
clients.update
campaigns.view
campaigns.create
campaigns.update
campaigns.approve
media.view
media.approve
media.reject
establishments.view
establishments.create
establishments.update
screens.view
screens.update
devices.view
devices.manage
reports.view
audit.view
```

## 4.3 Cliente

Empresa/anunciante que compra publicidade.

Futuramente poderá:

- visualizar suas campanhas;
- visualizar seus vídeos e imagens;
- enviar novas mídias;
- solicitar alteração de conteúdo;
- acompanhar status de aprovação;
- visualizar locais de exibição;
- visualizar exibições e relatórios;
- alterar dados básicos da conta.

O cliente não poderá publicar diretamente na TV sem aprovação, salvo futura permissão específica.

---

# 5. Usuários Internos

Área:

```text
Admin / Suporte
→ Usuários
```

Campos sugeridos:

```text
id
name
last_name
email
phone
password
role
is_active
avatar
last_login_at
last_login_ip
created_at
updated_at
```

Funcionalidades:

- listar;
- buscar;
- cadastrar;
- editar;
- ativar;
- desativar;
- redefinir senha;
- visualizar último acesso;
- atribuir permissões;
- visualizar histórico.

---

# 6. Permissões

Área:

```text
Admin
→ Permissões
```

Estrutura sugerida:

```text
permissions
roles
role_permissions
user_permissions
```

Cada permissão:

```text
slug
name
group
description
```

---

# 7. Clientes

Área interna:

```text
Admin / Suporte
→ Clientes
```

Campos:

```text
id
name
legal_name
document
email
phone
website
instagram
contact_name
category_id
status
notes
created_at
updated_at
```

Status:

```text
active
inactive
blocked
```

---

# 8. Usuários dos Clientes

Um cliente poderá possuir um ou mais usuários.

```text
Cliente
├── Usuário 1
├── Usuário 2
└── Usuário 3
```

O usuário representa a conta de acesso; o cliente representa a empresa anunciante.

---

# 9. Portal do Cliente

Área futura:

```text
/app/cliente
```

Views:

```text
Dashboard
Minhas campanhas
Minhas mídias
Enviar conteúdo
Solicitações
Relatórios
Minha empresa
Meu perfil
```

---

# 10. Dashboard Administrativo

View:

```text
/app/admin
```

Cards:

```text
Estabelecimentos ativos
Telas instaladas
Telas online
Telas offline
Clientes ativos
Campanhas ativas
Campanhas aguardando aprovação
Mídias aguardando aprovação
Exibições hoje
```

Também mostrar:

- TVs offline;
- dispositivos com pouco espaço;
- dispositivos desatualizados;
- campanhas próximas do fim;
- conteúdos aguardando aprovação;
- erros recentes;
- últimas ações administrativas.

---

# 11. Dashboard do Cliente

Cards:

```text
Campanhas ativas
Campanhas em análise
Campanhas finalizadas
Mídias enviadas
Exibições hoje
Exibições no mês
```

Também mostrar últimos uploads, rejeições, locais de exibição e gráficos.

---

# 12. Campanhas

Cada campanha pertence obrigatoriamente a um cliente.

```text
Client
└── Campaigns
```

Campos:

```text
id
client_id
name
description
start_date
end_date
status
priority
daily_limit
total_limit
created_by
approved_by
approved_at
created_at
updated_at
```

Status:

```text
draft
pending_approval
approved
scheduled
active
paused
rejected
completed
cancelled
```

---

# 13. View de Campanha

```text
Campanha
├── Visão geral
├── Mídias
├── Locais
├── Agendamento
├── Frequência
├── Aprovação
├── Exibições
└── Histórico
```

---

# 14. Mídias

Tipos iniciais:

```text
video
image
```

Formatos:

```text
Vídeo: MP4 / H.264
Imagem: WebP / JPEG / PNG
```

Campos:

```text
id
client_id
name
type
file_path
file_size
mime_type
duration
width
height
checksum
status
uploaded_by
approved_by
approved_at
rejection_reason
created_at
updated_at
```

Status:

```text
processing
pending_approval
approved
rejected
archived
```

---

# 15. Fluxo de Upload pelo Cliente

```text
Cliente
↓
envia vídeo/imagem
↓
Laravel recebe e valida
↓
processa
↓
status = pending_approval
↓
Admin/Suporte analisa
```

Aprovado:

```text
approved
↓
pode entrar em campanha
↓
playlist atualizada
↓
dispositivos sincronizam
```

Rejeitado:

```text
rejected
↓
cliente recebe motivo
```

---

# 16. Aprovação de Conteúdo

View:

```text
Admin / Suporte
→ Aprovações
```

Filtros:

```text
Aguardando
Aprovados
Rejeitados
Cliente
Tipo
Data
```

Ações:

```text
Visualizar
Reproduzir
Aprovar
Rejeitar
Adicionar observação
```

---

# 17. Estabelecimentos

Campos:

```text
id
name
legal_name
document
phone
email
contact_name
address
number
complement
neighborhood
city
state
zip_code
latitude
longitude
status
opening_hours
notes
created_at
updated_at
```

Cada estabelecimento poderá ter telas, contrato, valor mensal, conteúdo próprio, categorias bloqueadas e dispositivos.

---

# 18. Contrato do Estabelecimento

Campos:

```text
start_date
end_date
monthly_payment
payment_day
free_ad_percentage
revenue_share
status
notes
```

---

# 19. Telas

```text
Estabelecimento
└── Tela
    └── Dispositivo
```

Campos:

```text
id
establishment_id
name
description
orientation
resolution
location_description
status
created_at
updated_at
```

---

# 20. Dispositivos

Cada PC Linux será um dispositivo.

Campos:

```text
id
screen_id
device_code
device_secret_hash
hostname
os_version
agent_version
player_version
last_seen_at
last_ip
storage_total
storage_free
uptime
status
registered_at
created_at
updated_at
```

Status:

```text
pending
online
offline
maintenance
disabled
```

---

# 21. View de Dispositivo

Mostrar:

```text
Status
Último contato
Playlist
Versão do Agent
Versão do Player
Armazenamento
Uptime
Último erro
```

Ações futuras:

```text
Sincronizar agora
Reiniciar player
Reiniciar agent
Limpar cache
Capturar screenshot
Coletar logs
Desativar dispositivo
```

---

# 22. Provisionamento

```text
Node Agent inicia
↓
solicita registro
↓
Laravel gera código
↓
TV mostra código
↓
Admin associa código a estabelecimento/tela
↓
dispositivo é ativado
```

---

# 23. Playlist

Laravel determina o conteúdo de cada TV.

```json
{
  "version": 42,
  "items": [
    {
      "media_id": 87,
      "type": "video",
      "url": "https://cdn.exemplo.com/87.mp4",
      "sha256": "...",
      "duration": 15
    }
  ]
}
```

---

# 24. Versionamento da Playlist

```text
playlist 41
↓
nova campanha aprovada
↓
playlist 42
```

O dispositivo poderá consultar apenas:

```text
GET /api/device/playlist/version
```

Se não mudou, nenhuma mídia é baixada.

---

# 25. API Central do Laravel

Rotas sugeridas:

```text
POST /api/device/register
POST /api/device/activate
GET  /api/device/configuration
GET  /api/device/playlist/version
GET  /api/device/playlist
POST /api/device/heartbeat
POST /api/device/events
POST /api/device/errors
```

---

# 26. Autenticação dos Dispositivos

Cada equipamento possui:

```text
device_id
device_secret
```

Nunca utilizar uma única chave global.

---

# 27. Digital Signage Device

Estrutura:

```text
digital-signage-device/
├── src/
│   ├── api/
│   ├── auth/
│   ├── sync/
│   ├── downloader/
│   ├── storage/
│   ├── heartbeat/
│   ├── events/
│   ├── watchdog/
│   ├── player/
│   └── index.js
├── storage/
│   ├── media/
│   ├── database/
│   ├── logs/
│   └── playlist.json
└── package.json
```

---

# 28. Rotinas do Node Agent

O Agent ficará ativo com `systemd`.

```text
a cada 30s  → heartbeat
a cada 60s  → verificar versão da playlist
a cada 10s  → watchdog do player
a cada 5min → sincronizar eventos pendentes
a cada 1h   → verificar armazenamento/cache
```

Ao iniciar, sincroniza imediatamente.

---

# 29. Download das Mídias

```text
Laravel
↓
playlist nova
↓
Node compara com playlist local
↓
identifica arquivos ausentes
↓
baixa
↓
valida checksum
↓
salva no SSD
↓
ativa playlist
```

---

# 30. Cache Local

```text
/opt/signage/media/
├── 87.mp4
├── 91.webp
└── 102.mp4
```

O player consome arquivos locais.

---

# 31. Atualização Atômica

Nunca ativar playlist antes de todos os arquivos estarem válidos.

```text
recebe playlist
↓
baixa arquivos
↓
valida
↓
todos OK?
↓
ativa
```

Enquanto isso, a playlist anterior continua rodando.

---

# 32. Funcionamento Offline

Obrigatório:

```text
Internet ❌
Laravel ❌

Node Agent ✅
SSD ✅
Player ✅
TV ✅
```

---

# 33. Heartbeat

Exemplo:

```json
{
  "playlist_version": 42,
  "agent_version": "1.0.0",
  "player_version": "1.0.0",
  "storage_total": 128000000000,
  "storage_free": 86000000000,
  "uptime": 848294,
  "current_media_id": 91
}
```

Considerar offline inicialmente após 5 minutos sem heartbeat.

---

# 34. Registro de Exibições

Eventos:

```text
playback_started
playback_completed
playback_failed
```

---

# 35. Fila Offline

Usar SQLite para eventos pendentes.

```text
evento
↓
sem internet
↓
fica local
↓
internet retorna
↓
envia ao Laravel
```

---

# 36. Player Local

Servidor local:

```text
http://127.0.0.1:3000
```

Chromium:

```bash
chromium --kiosk http://127.0.0.1:3000
```

---

# 37. Player

Requisitos:

- vídeo;
- imagem;
- autoplay;
- fullscreen;
- sem controles;
- preload;
- loop;
- pular mídia com erro;
- informar início/fim ao Agent.

---

# 38. Agendamento

Campanhas podem ter:

```text
datas
dias da semana
horários
```

---

# 39. Frequência

```text
Básico   peso 1
Premium  peso 2
Destaque peso 5
```

---

# 40. Conteúdo do Estabelecimento

Exemplo:

```text
80% anúncios comerciais
20% conteúdo do estabelecimento
```

---

# 41. Bloqueio de Concorrentes

Exemplo:

```text
Academia Strong
→ bloquear categoria Academias
```

---

# 42. Relatórios

Filtros:

- cliente;
- campanha;
- estabelecimento;
- tela;
- cidade;
- mídia;
- período.

Usar o termo **exibições**, não pessoas alcançadas sem medição real.

---

# 43. Monitoramento

View:

```text
Admin / Suporte
→ Monitoramento
```

Mostrar:

```text
Online
Offline
Último heartbeat
Agent
Player
Playlist
Espaço livre
Uptime
Último erro
```

---

# 44. Alertas

```text
TV offline
Disco quase cheio
Playlist atrasada
Erro de download
Erro de mídia
Agent antigo
Campanha próxima do fim
Mídia aguardando aprovação
```

---

# 45. Auditoria

Registrar:

```text
user_id
module
action
description
auditable_type
auditable_id
old_values
new_values
ip
user_agent
created_at
```

---

# 46. Notificações

Exemplos:

```text
Nova mídia aguardando aprovação
TV offline
Campanha aprovada
Campanha rejeitada
Campanha próxima do fim
Cliente enviou novo vídeo
```

---

# 47. Financeiro

Fase posterior:

```text
Planos
Cobranças
Recebimentos
Pagamentos aos estabelecimentos
Resumo financeiro
```

---

# 48. Métricas Comerciais

```text
MRR
Receita por tela
Custo por tela
Margem por tela
Receita por cliente
Ticket médio
Ocupação da grade
Campanhas ativas
Exibições por tela
```

---

# 49. Menu Admin/Suporte

```text
Dashboard

Operação
├── Campanhas
├── Aprovações
├── Mídias
├── Playlists
└── Alertas

Rede
├── Estabelecimentos
├── Telas
├── Dispositivos
└── Monitoramento

Clientes
├── Clientes
└── Usuários dos clientes

Relatórios
├── Campanhas
├── Exibições
├── Dispositivos
└── Clientes

Administração
├── Usuários
├── Permissões
├── Auditoria
└── Configurações

Financeiro
├── Cobranças
├── Pagamentos
└── Resumo
```

---

# 50. Menu do Cliente

```text
Dashboard

Publicidade
├── Minhas campanhas
├── Minhas mídias
├── Enviar mídia
└── Solicitações

Resultados
└── Relatórios

Conta
├── Minha empresa
├── Meu perfil
└── Segurança
```

---

# 51. Ordem de Implementação — Laravel/Vue

## Fase 1

```text
[ ] Login com Sanctum
[ ] Perfil
[ ] Usuários internos
[ ] Permissões
[ ] Dashboard básico
```

## Fase 2

```text
[ ] Clientes
[ ] Usuários de clientes
[ ] Categorias
[ ] Estabelecimentos
[ ] Telas
[ ] Dispositivos
```

## Fase 3

```text
[ ] Mídias
[ ] Campanhas
[ ] Aprovação
[ ] Segmentação
[ ] Agendamento
```

## Fase 4

```text
[ ] Playlists
[ ] API para dispositivos
[ ] Device authentication
[ ] Heartbeat
[ ] Monitoramento
```

## Fase 5

```text
[ ] Eventos de reprodução
[ ] Relatórios
[ ] Auditoria completa
[ ] Alertas
```

## Fase 6

```text
[ ] Portal do cliente
[ ] Upload pelo cliente
[ ] Aprovação de alterações
[ ] Relatórios do cliente
```

## Fase 7

```text
[ ] Financeiro
[ ] Contratos
[ ] Pagamentos
[ ] Métricas comerciais
```

---

# 52. Ordem de Implementação — Node Device

## Fase 1

```text
[ ] Projeto Node
[ ] Configuração local
[ ] Logs
[ ] systemd
```

## Fase 2

```text
[ ] Provisionamento
[ ] Device ID
[ ] Device Secret
[ ] Comunicação com Laravel
```

## Fase 3

```text
[ ] Consulta de playlist
[ ] Versionamento
[ ] Download
[ ] Checksum
[ ] Cache
```

## Fase 4

```text
[ ] Player local
[ ] Chromium kiosk
[ ] Vídeos
[ ] Imagens
[ ] Loop
```

## Fase 5

```text
[ ] Heartbeat
[ ] Watchdog
[ ] Operação offline
[ ] Recuperação após reiniciar
```

## Fase 6

```text
[ ] Playback events
[ ] SQLite
[ ] Fila offline
[ ] Reenvio automático
```

## Fase 7

```text
[ ] Atualização remota
[ ] Screenshot
[ ] Coleta remota de logs
[ ] Limpeza de cache
```

---

# 53. Primeiro Marco Comercial

```text
Admin envia vídeo
↓
Admin cria campanha
↓
Admin seleciona TV
↓
campanha é aprovada
↓
Laravel gera nova playlist
↓
Node detecta nova versão
↓
Node baixa vídeo
↓
TV começa a exibir
↓
Node registra exibição
↓
Laravel recebe evento
↓
Dashboard mostra resultado
```

Tudo sem acessar fisicamente o computador do estabelecimento.

---

# 54. Regra Principal

A TV nunca deverá parar por causa de:

```text
internet caída
API Laravel indisponível
CDN indisponível
```

Fluxo obrigatório:

```text
sincroniza
↓
baixa
↓
valida
↓
salva localmente
↓
reproduz localmente
```

---

# 55. Visão de Longo Prazo

A arquitetura deve suportar evolução de:

```text
1 TV
↓
10 TVs
↓
100 TVs
↓
500 TVs
↓
1.000+ TVs
```

Laravel será responsável pelo gerenciamento central e regras de negócio.

Node.js será responsável por manter cada dispositivo de publicidade funcionando de forma autônoma no estabelecimento.