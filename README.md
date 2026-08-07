# Digital Signage — Plataforma de Publicidade em TVs

Sistema de gerenciamento de publicidade digital para exibição de anúncios em TVs instaladas em estabelecimentos parceiros.

A solução será dividida em dois componentes principais:

1. **Plataforma Central** — painel administrativo e API para gerenciamento de clientes, estabelecimentos, telas, campanhas, mídias, playlists, agendamentos, relatórios e dispositivos.
2. **TV Player / Signage Agent** — software instalado em um PC ou mini PC com Linux conectado à TV, responsável por sincronizar conteúdo, armazenar vídeos localmente, reproduzir anúncios e reportar o funcionamento ao servidor.

---

# 1. Visão Geral

A plataforma permitirá criar uma rede própria de TVs publicitárias distribuídas em estabelecimentos como:

- Academias
- Barbearias
- Clínicas
- Consultórios
- Mercados
- Restaurantes
- Salões
- Oficinas
- Lojas
- Recepções
- Outros locais com fluxo ou permanência de pessoas

Cada estabelecimento poderá possuir uma ou mais telas.

Os anunciantes poderão contratar campanhas para aparecer em telas específicas, grupos de telas, regiões ou toda a rede.

---

# 2. Arquitetura Geral

```text
                    ┌─────────────────────────┐
                    │      Painel Web         │
                    │       Vue 3             │
                    └────────────┬────────────┘
                                 │
                                 ▼
                    ┌─────────────────────────┐
                    │       API Node.js       │
                    │   Fastify / Express     │
                    └───────┬─────────┬───────┘
                            │         │
                            │         └─────────────┐
                            ▼                       ▼
                     PostgreSQL               S3 / R2
                                                 │
                                                 │ mídias
                 ┌───────────────────────────────┘
                 │
        ┌────────┴────────┐
        │                 │
        ▼                 ▼
  PC Linux / TV 01   PC Linux / TV 02
        │                 │
        ▼                 ▼
     Player            Player
        │                 │
        ▼                 ▼
       TV                TV
```

---

# 3. Stack Sugerida

## Backend

- Node.js
- Fastify ou Express
- JavaScript ou TypeScript
- PostgreSQL
- Prisma, Sequelize ou SQL puro
- Redis opcional
- JWT apenas para APIs externas, se necessário
- Sessão/cookie para painel administrativo, quando aplicável

## Frontend administrativo

- Vue 3
- Vite
- PrimeVue
- Pinia
- Vue Router

## Armazenamento

- Cloudflare R2
- Amazon S3
- MinIO em desenvolvimento

## Software da TV

- Linux
- Node.js
- Chromium
- HTML5 Video
- systemd
- armazenamento local em SSD

## Infraestrutura

- Docker para servidor
- Nginx
- HTTPS
- CI/CD
- Logs centralizados
- Backup do banco

---

# 4. Componentes do Sistema

## 4.1 Plataforma Central

Responsável por:

- usuários
- clientes
- anunciantes
- estabelecimentos
- telas
- dispositivos
- campanhas
- vídeos
- playlists
- agendamentos
- regras de frequência
- relatórios
- monitoramento
- financeiro
- contratos
- auditoria

## 4.2 Signage Agent

Responsável por:

- autenticar o dispositivo
- consultar configurações
- sincronizar playlists
- baixar mídias
- validar arquivos
- armazenar conteúdo localmente
- remover arquivos antigos
- iniciar o player
- monitorar o player
- enviar heartbeat
- registrar reproduções
- funcionar offline
- recuperar-se após falhas

## 4.3 Player

Responsável apenas pela apresentação do conteúdo:

- reproduzir vídeos
- reproduzir imagens
- alternar conteúdos
- respeitar duração
- respeitar playlist
- operar em tela cheia
- esconder cursor
- evitar interrupções visuais
- informar ao Agent o início/fim das reproduções

---

# 5. Requisitos da Plataforma Central

# 5.1 Autenticação

O sistema deverá possuir autenticação para operadores.

Requisitos:

- login
- logout
- recuperação de senha
- alteração de senha
- bloqueio de usuário
- controle de sessões
- autenticação em dois fatores futuramente
- proteção contra brute force

---

# 5.2 Usuários e Papéis

Papéis iniciais:

### Administrador

Acesso completo.

### Operador

Pode administrar conteúdo e campanhas.

### Comercial

Pode administrar anunciantes, propostas e contratos.

### Financeiro

Pode administrar cobranças e pagamentos.

### Cliente/Anunciante

Futuramente poderá visualizar suas campanhas e relatórios.

---

# 5.3 Estabelecimentos Parceiros

Campos sugeridos:

```text
id
name
legal_name
document
phone
email
address
number
complement
neighborhood
city
state
zip_code
latitude
longitude
contact_name
status
opening_hours
notes
created_at
updated_at
```

O estabelecimento deverá possuir:

- responsável
- endereço
- contrato
- valor mensal pago pelo espaço
- porcentagem/espaço reservado para conteúdo próprio
- categorias concorrentes bloqueadas
- horário de funcionamento
- quantidade de telas

---

# 5.4 Contrato do Estabelecimento

Registrar:

```text
establishment_id
start_date
end_date
monthly_payment
payment_day
free_ad_slots
revenue_share
status
notes
```

Possíveis modelos:

### Aluguel fixo

Exemplo:

```text
R$ 200/mês
```

### Aluguel reduzido + publicidade gratuita

Exemplo:

```text
R$ 100/mês
+
20% da programação para o estabelecimento
```

### Apenas publicidade gratuita

Exemplo:

```text
R$ 0/mês
+
espaço gratuito de divulgação
```

---

# 5.5 Telas

Uma localização poderá ter várias telas.

Campos:

```text
id
establishment_id
name
description
orientation
resolution
status
location_description
created_at
updated_at
```

Exemplo:

```text
Academia Strong
└── TV Recepção
└── TV Área de Cardio
```

---

# 5.6 Dispositivos

Cada PC Linux será tratado como um dispositivo.

Campos:

```text
id
screen_id
device_code
device_secret_hash
hostname
mac_address
ip_address
os_version
agent_version
player_version
last_seen_at
last_ip
storage_total
storage_free
status
registered_at
created_at
updated_at
```

Estados possíveis:

```text
pending
online
offline
maintenance
disabled
```

---

# 5.7 Provisionamento

Um dispositivo novo deverá poder ser associado sem configuração manual complexa.

Fluxo desejado:

```text
Linux inicia
↓
Signage Agent inicia
↓
Solicita registro
↓
Servidor fornece código
↓
TV mostra:

DEVICE CODE
8HK-38F

↓
Administrador abre painel
↓
Seleciona estabelecimento/tela
↓
Informa 8HK-38F
↓
Servidor associa dispositivo
↓
Agent recebe credenciais
↓
Sincronização começa
```

---

# 5.8 Anunciantes

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

---

# 5.9 Categorias

Exemplos:

```text
Academia
Restaurante
Pizzaria
Dentista
Imobiliária
Barbearia
Mercado
Farmácia
Clínica
Loja de roupas
```

As categorias serão importantes para evitar concorrência.

Exemplo:

```text
Estabelecimento:
Academia Strong

Categoria bloqueada:
Academias
```

Nenhuma campanha de outra academia será exibida nessa localização.

---

# 5.10 Mídias

Suporte inicial:

- vídeo MP4
- imagem JPEG
- imagem PNG
- imagem WebP

Futuramente:

- HTML
- páginas web
- feeds
- widgets
- notícias
- previsão do tempo
- QR Codes dinâmicos

Campos:

```text
id
advertiser_id
name
type
file_url
file_size
duration
width
height
codec
checksum
status
created_at
updated_at
```

---

# 5.11 Padronização de Vídeos

Formato recomendado:

```text
Container: MP4
Codec: H.264
Resolução: 1920x1080
Frame rate: 30 FPS
Áudio: AAC
```

A plataforma deverá opcionalmente converter vídeos enviados para um padrão conhecido.

Pode utilizar:

```text
FFmpeg
```

---

# 5.12 Campanhas

Campos:

```text
id
advertiser_id
name
start_date
end_date
status
priority
daily_limit
total_limit
created_at
updated_at
```

Estados:

```text
draft
scheduled
active
paused
completed
cancelled
```

---

# 5.13 Segmentação

Uma campanha poderá ser exibida em:

- uma tela
- várias telas
- estabelecimento
- vários estabelecimentos
- cidade
- bairro
- grupo
- toda a rede

---

# 5.14 Agendamento

Uma campanha deverá suportar:

### Intervalo de datas

```text
01/08/2026 até 31/08/2026
```

### Dias da semana

```text
segunda
terça
quarta
quinta
sexta
```

### Horários

```text
11:00 - 14:00
```

Exemplo:

```text
Campanha Restaurante X

Segunda a sexta
11:00 até 14:30
```

---

# 5.15 Frequência de Exibição

O sistema deverá permitir diferentes pesos.

Exemplo:

```text
Plano Básico
peso 1

Plano Premium
peso 2

Plano Destaque
peso 5
```

Uma playlist poderá ser gerada considerando esses pesos.

---

# 5.16 Limite de Exibições

Opcionalmente uma campanha poderá contratar:

```text
100 exibições/dia
```

ou:

```text
10.000 exibições durante a campanha
```

O sistema deverá controlar esses limites.

---

# 5.17 Conteúdo do Próprio Estabelecimento

Cada estabelecimento poderá receber uma porcentagem da grade.

Exemplo:

```text
80% anúncios comerciais
20% conteúdo do estabelecimento
```

O estabelecimento poderá possuir várias mídias próprias.

Exemplo:

```text
Promoção da semana
Instagram
WhatsApp
Horário de funcionamento
Cardápio
```

---

# 5.18 Playlists

Uma playlist será a lista de conteúdos que uma tela deverá reproduzir.

Exemplo:

```json
{
  "version": 38,
  "screen_id": 12,
  "items": [
    {
      "campaign_id": 93,
      "media_id": 203,
      "duration": 15
    },
    {
      "campaign_id": 96,
      "media_id": 211,
      "duration": 20
    }
  ]
}
```

Toda alteração deverá incrementar uma versão.

Exemplo:

```text
playlist 37
↓
nova campanha adicionada
↓
playlist 38
```

---

# 5.19 Dashboard

Dashboard inicial:

```text
Telas
25

Online
23

Offline
2

Campanhas ativas
42

Anunciantes
31

Exibições hoje
128.430
```

Mostrar também:

- telas offline
- erros recentes
- pouco armazenamento
- dispositivos desatualizados
- campanhas terminando
- campanhas sem mídia
- campanhas aguardando aprovação

---

# 5.20 Monitoramento dos Dispositivos

Exemplo:

```text
TV Recepção - Academia Strong

Status: Online
Último contato: 12 segundos
Agent: 1.2.4
Playlist: 38
Disco livre: 76 GB
IP: xxx.xxx.xxx.xxx
Tempo ligado: 15 dias
```

---

# 5.21 Heartbeat

O dispositivo enviará periodicamente:

```http
POST /api/devices/heartbeat
```

Payload aproximado:

```json
{
  "device_id": "TV-SP-0001",
  "agent_version": "1.0.0",
  "player_version": "1.0.0",
  "playlist_version": 38,
  "current_media_id": 211,
  "disk_total": 128000000000,
  "disk_free": 86000000000,
  "uptime": 848294
}
```

Sugestão:

```text
heartbeat a cada 60 segundos
```

Considerar offline após:

```text
5 minutos sem heartbeat
```

---

# 5.22 Registro de Exibições

Registrar o início e a conclusão da reprodução.

Eventos:

```text
playback_started
playback_completed
playback_failed
```

Exemplo:

```json
{
  "device_id": "TV-SP-0001",
  "campaign_id": 33,
  "media_id": 89,
  "started_at": "2026-08-07T15:20:00Z",
  "finished_at": "2026-08-07T15:20:15Z",
  "completed": true
}
```

---

# 5.23 Operação Offline dos Logs

Caso a internet esteja indisponível:

```text
Player reproduz
↓
Agent registra evento localmente
↓
Internet indisponível
↓
evento fica em fila
↓
internet retorna
↓
eventos são enviados ao servidor
```

Não perder registros por falha temporária de internet.

---

# 5.24 Relatórios

Relatórios por:

- anunciante
- campanha
- estabelecimento
- tela
- cidade
- período
- mídia

Exemplo:

```text
Campanha: Pizzaria Bella

Período:
01/08/2026 - 31/08/2026

Academia Strong: 4.821
Mercado Central: 5.920
Clínica Saúde: 3.641

Total de exibições:
14.382
```

Importante:

O sistema deve usar o termo:

```text
exibições
```

e não afirmar que foram:

```text
14.382 pessoas
```

sem uma tecnologia própria de medição de audiência.

---

# 5.25 Auditoria

Registrar ações administrativas:

```text
user_id
action
entity
entity_id
old_data
new_data
ip
user_agent
created_at
```

Exemplos:

```text
Campanha criada
Campanha pausada
Vídeo removido
Dispositivo desativado
Playlist alterada
```

---

# 6. Requisitos do Signage Agent

O Agent será um serviço Node.js executado no Linux.

Exemplo:

```text
/opt/signage/
├── agent/
├── player/
├── media/
├── data/
├── logs/
└── config/
```

---

# 6.1 Inicialização Automática

Criar serviço:

```text
signage-agent.service
```

Utilizando:

```text
systemd
```

Fluxo:

```text
PC liga
↓
Linux inicia
↓
signage-agent.service
↓
Agent inicia
↓
Player inicia
↓
propagandas começam
```

---

# 6.2 Auto Login

Caso o player dependa de sessão gráfica:

- configurar usuário dedicado
- habilitar auto login
- iniciar ambiente gráfico mínimo
- iniciar Chromium automaticamente

---

# 6.3 Kiosk Mode

Executar Chromium em modo:

```bash
chromium --kiosk http://localhost:3000
```

Adicionalmente:

- remover barra de navegação
- esconder cursor
- impedir sleep
- impedir screensaver
- impedir notificações
- impedir atualizações abrindo popups

---

# 6.4 Player Local

O Agent deverá disponibilizar uma aplicação local:

```text
http://localhost:3000
```

O Chromium acessará apenas esse endereço.

Benefício:

O player não depende da disponibilidade da internet para abrir.

---

# 6.5 Cache de Mídias

Mídias deverão ser armazenadas em:

```text
/opt/signage/media/
```

Exemplo:

```text
/opt/signage/media/89.mp4
/opt/signage/media/103.mp4
/opt/signage/media/145.jpg
```

---

# 6.6 Sincronização

Fluxo:

```text
Agent consulta API
↓
API informa playlist_version = 38
↓
Agent possui version = 37
↓
baixa manifesto
↓
identifica arquivos faltantes
↓
baixa conteúdo
↓
valida arquivos
↓
ativa playlist 38
```

---

# 6.7 Atualização Atômica

Nunca ativar playlist antes de baixar todos os arquivos necessários.

Errado:

```text
recebe playlist
↓
ativa imediatamente
↓
vídeo ainda não foi baixado
↓
erro
```

Correto:

```text
recebe playlist
↓
baixa tudo
↓
valida tudo
↓
salva nova playlist
↓
troca playlist ativa
```

---

# 6.8 Checksum

Cada mídia deverá possuir:

```text
SHA-256
```

Após o download:

```text
download
↓
calcular SHA-256
↓
comparar com servidor
```

Se inválido:

```text
descartar
↓
baixar novamente
```

---

# 6.9 Downloads Resumíveis

Futuramente implementar downloads que possam continuar após interrupções.

Útil para vídeos grandes e internet instável.

---

# 6.10 Limpeza de Arquivos

O Agent deverá identificar mídias não utilizadas.

Exemplo:

```text
playlist antiga:
1.mp4
2.mp4
3.mp4

playlist nova:
1.mp4
4.mp4

arquivos removíveis:
2.mp4
3.mp4
```

A limpeza deverá ocorrer apenas após confirmar que a nova playlist está funcional.

---

# 6.11 Espaço em Disco

Definir limites.

Exemplo:

```text
warning:
menos de 10 GB

critical:
menos de 3 GB
```

Reportar ao servidor.

---

# 6.12 Funcionamento sem Internet

Requisito obrigatório.

Se a internet cair:

```text
API ❌
Internet ❌

Player:
✅ continua

Playlist local:
✅ continua

Vídeos locais:
✅ continuam
```

O sistema deverá continuar indefinidamente com a última playlist válida.

---

# 6.13 Recuperação após Reinicialização

Após falta de energia:

```text
energia retorna
↓
PC liga
↓
Linux inicia
↓
Agent inicia
↓
última playlist válida
↓
Player inicia
```

Não depender da internet para recuperar a reprodução.

---

# 6.14 Watchdog do Player

O Agent deverá verificar se o Chromium/player está funcionando.

Caso o processo pare:

```text
Chromium morreu
↓
Agent detecta
↓
reinicia Chromium
```

---

# 6.15 Watchdog da Reprodução

O player deverá informar continuamente seu estado.

Exemplo:

```text
media_id: 89
position: 12.4s
state: playing
```

Se ficar travado por determinado período:

```text
Agent reinicia Player
```

---

# 6.16 Logs Locais

Registrar:

```text
agent.log
player.log
sync.log
errors.log
```

Implementar rotação para evitar lotar o SSD.

---

# 6.17 Fila Offline

Usar banco local simples, por exemplo:

```text
SQLite
```

para:

- reproduções pendentes
- erros
- heartbeat
- comandos pendentes

---

# 6.18 Relógio

Horários corretos são essenciais para agendamento.

Configurar:

```text
NTP
```

O Agent deverá detectar relógio significativamente incorreto.

---

# 7. Requisitos do Player

# 7.1 Reprodução de Vídeos

Utilizar:

```html
<video>
```

Requisitos:

- autoplay
- muted por padrão
- fullscreen
- preload
- sem controles
- transição rápida
- tratamento de erro

---

# 7.2 Imagens

Imagens terão duração configurável.

Exemplo:

```text
banner.jpg
duration: 10 segundos
```

---

# 7.3 Loop

Ao chegar ao último conteúdo:

```text
último
↓
primeiro
```

sem tela preta prolongada.

---

# 7.4 Preload

O próximo conteúdo deverá ser preparado antes do término do atual sempre que possível.

Objetivo:

reduzir tela preta entre anúncios.

---

# 7.5 Falha em uma mídia

Se uma mídia falhar:

```text
registrar erro
↓
pular mídia
↓
continuar playlist
```

Nunca interromper toda a programação por causa de um único arquivo.

---

# 7.6 Áudio

Configuração por tela:

```text
enabled
disabled
volume
```

Padrão recomendado:

```text
muted
```

Muitos estabelecimentos não desejarão áudio publicitário.

---

# 7.7 Orientação

Suportar:

```text
landscape
portrait
```

Exemplos:

```text
1920x1080

1080x1920
```

---

# 8. Comunicação Agent ↔ Servidor

Endpoints sugeridos:

```text
POST /api/devices/register

POST /api/devices/activate

POST /api/devices/heartbeat

GET /api/devices/{id}/configuration

GET /api/devices/{id}/playlist

POST /api/devices/{id}/events

POST /api/devices/{id}/errors
```

---

# 9. Segurança dos Dispositivos

Cada dispositivo deverá possuir credenciais próprias.

Nunca:

```text
UMA_CHAVE_GLOBAL_PARA_TODAS_AS_TVS
```

Usar:

```text
device_id
device_secret
```

Exemplo:

```text
TV-001 -> secret A
TV-002 -> secret B
TV-003 -> secret C
```

Assim um dispositivo comprometido pode ser revogado individualmente.

---

# 9.1 Armazenamento das Credenciais

No servidor:

```text
hash do secret
```

No dispositivo:

arquivo protegido:

```text
/etc/signage/device.json
```

Permissão:

```text
600
```

---

# 9.2 HTTPS

Toda comunicação externa deverá utilizar:

```text
HTTPS
```

Nunca enviar credenciais por HTTP.

---

# 9.3 Assinatura de Requisições

Futuramente considerar:

- HMAC
- timestamp
- nonce

para diminuir risco de replay.

---

# 9.4 Revogação

O painel deverá permitir:

```text
Desativar dispositivo
```

Após isso:

```text
API recusa autenticação
```

---

# 10. Comandos Remotos

Futuramente o servidor poderá enviar comandos.

Exemplos:

```text
restart_player
restart_agent
restart_device
sync_playlist
clear_cache
update_agent
take_screenshot
collect_logs
```

Comandos deverão ser autenticados e auditados.

---

# 11. Screenshot Remoto

Funcionalidade futura útil.

O administrador solicita:

```text
Capturar tela
```

O dispositivo envia screenshot.

Isso permite verificar remotamente o que está sendo exibido.

Deve ser usado apenas para capturar a saída digital da própria programação da tela, não câmeras ou pessoas do ambiente.

---

# 12. Atualização do Agent

Versões:

```text
1.0.0
1.0.1
1.1.0
```

Dashboard:

```text
TV-001  1.1.0
TV-002  1.1.0
TV-003  1.0.0 ⚠
```

Atualização deverá possuir:

- checksum
- assinatura opcional
- rollback
- atualização gradual

---

# 13. Banco de Dados — Entidades Principais

Estrutura inicial:

```text
users

establishments
establishment_contracts

screens
devices

advertisers
categories

media

campaigns
campaign_targets
campaign_schedules
campaign_media

playlists
playlist_items

playback_events

device_heartbeats
device_errors
device_commands

invoices
payments

audit_logs
```

---

# 14. Relacionamentos

```text
Establishment
    └── Screens
            └── Device


Advertiser
    └── Campaigns
            └── Media


Campaign
    └── Targets
            ├── Screens
            ├── Establishments
            └── Groups
```

---

# 15. Financeiro

Fase futura.

Gerenciar:

## Receita

Cobrança dos anunciantes.

Campos:

```text
advertiser_id
campaign_id
amount
due_date
paid_at
status
```

## Custo

Pagamento aos estabelecimentos.

Campos:

```text
establishment_id
amount
due_date
paid_at
status
```

---

# 16. Métricas de Negócio

Dashboard futuro:

```text
MRR

Receita por tela

Custo por tela

Margem por tela

Número de anunciantes

Receita média por anunciante

Taxa de ocupação da grade

Exibições por tela

Estabelecimentos ativos
```

Exemplo:

```text
TV Academia Strong

Receita:
R$ 1.400

Pagamento ao estabelecimento:
R$ 150

Infraestrutura:
R$ 30

Margem antes de impostos:
R$ 1.220
```

---

# 17. Alertas

Gerar alerta para:

```text
TV offline > 5 minutos

Disco quase cheio

Playlist desatualizada

Falha repetida em mídia

Agent antigo

Campanha sem mídia

Campanha próxima do fim

Dispositivo sem sincronizar
```

---

# 18. Requisitos Não Funcionais

## Disponibilidade

A reprodução local deve continuar mesmo se:

- API estiver offline
- internet cair
- CDN estiver indisponível

## Performance

Transição entre anúncios deverá ser rápida.

## Escalabilidade

A arquitetura deverá suportar inicialmente:

```text
10 TVs
```

e crescer para:

```text
100
500
1.000+
```

sem alteração completa da arquitetura.

## Observabilidade

Registrar:

- erros
- duração de downloads
- sincronizações
- falhas de reprodução
- heartbeats

## Segurança

- HTTPS
- credenciais individuais
- rate limit
- logs de auditoria
- validação de uploads
- controle de acesso

---

# 19. Hardware Recomendado para Player

O sistema não dependerá de Raspberry Pi.

Poderão ser utilizados:

- PCs antigos
- Mini PCs
- Dell OptiPlex Micro
- Lenovo ThinkCentre Tiny
- HP ProDesk Mini
- Raspberry Pi futuramente

Configuração sugerida:

```text
CPU:
dual-core ou superior

RAM:
4 GB+

Storage:
SSD 64/120 GB+

Vídeo:
saída HDMI

Rede:
Ethernet ou Wi-Fi
```

---

# 20. Sistema Operacional do Player

Sugestão:

```text
Debian
```

ou:

```text
Ubuntu
```

Para produção, preferir uma instalação mínima.

Não instalar aplicações desnecessárias.

---

# 21. Padronização dos Equipamentos

Quando o projeto crescer, criar uma imagem padrão do sistema.

Exemplo:

```text
signage-linux.img
```

Contendo:

```text
Linux
Node.js
Agent
Chromium
systemd service
configurações de kiosk
watchdog
```

Novo equipamento:

```text
instalar imagem
↓
ligar
↓
provisionar
```

---

# 22. MVP — Fase 1

Objetivo:

Uma única TV reproduzindo vídeos locais.

Implementar:

- Linux
- Chromium kiosk
- player HTML
- playlist JSON
- autoplay
- loop
- inicialização automática

Não implementar ainda:

- painel
- API
- clientes
- financeiro

---

# 23. MVP — Fase 2

Adicionar Agent Node.js.

Implementar:

- servidor local
- systemd
- watchdog
- armazenamento local
- logs
- SQLite

---

# 24. MVP — Fase 3

Adicionar API.

Implementar:

- dispositivos
- autenticação
- heartbeat
- playlist remota
- download de vídeos
- checksum
- cache offline

---

# 25. MVP — Fase 4

Adicionar painel administrativo.

Implementar:

- login
- estabelecimentos
- telas
- dispositivos
- anunciantes
- mídias
- campanhas
- playlists

---

# 26. MVP — Fase 5

Adicionar inteligência comercial.

Implementar:

- agendamento
- frequência
- peso
- segmentação
- bloqueio de concorrentes
- conteúdo gratuito do estabelecimento

---

# 27. MVP — Fase 6

Adicionar relatórios.

Implementar:

- playback events
- exibições por campanha
- exibições por tela
- relatórios por período
- exportação CSV/PDF futuramente

---

# 28. MVP — Fase 7

Adicionar financeiro.

Implementar:

- planos
- contratos
- cobranças
- pagamentos aos pontos
- margem por tela
- receita por campanha

---

# 29. Prioridades

## Obrigatório para primeira instalação comercial

```text
[ ] Inicialização automática
[ ] Player fullscreen
[ ] Cache local
[ ] Funcionamento offline
[ ] Sincronização remota
[ ] Heartbeat
[ ] Watchdog
[ ] Registro de reproduções
[ ] Segurança individual dos dispositivos
[ ] Dashboard de status
```

## Segunda prioridade

```text
[ ] Campanhas
[ ] Agendamento
[ ] Segmentação
[ ] Frequência
[ ] Relatórios
[ ] Conteúdo próprio do estabelecimento
```

## Terceira prioridade

```text
[ ] Financeiro
[ ] Portal do anunciante
[ ] Atualização remota
[ ] Screenshot remoto
[ ] Métricas avançadas
```

---

# 30. Regra Principal do Projeto

A TV nunca deve ficar inutilizada simplesmente porque o servidor ou a internet estão indisponíveis.

A arquitetura deve sempre seguir:

```text
Internet disponível
        ↓
sincroniza conteúdo
        ↓
salva localmente
        ↓
reproduz localmente
```

e nunca:

```text
TV
↓
stream permanente pela internet
```

---

# 31. Fluxo Final Esperado

```text
Administrador cria campanha
↓
envia vídeo
↓
seleciona telas
↓
configura período
↓
API gera nova playlist
↓
Agent detecta nova versão
↓
baixa mídia
↓
confere checksum
↓
ativa playlist
↓
TV reproduz anúncio
↓
Agent registra exibição
↓
evento é enviado ao servidor
↓
dashboard e relatório são atualizados
```

---

# 32. Objetivo de Produto

O objetivo não é simplesmente criar um player de vídeos.

O objetivo é criar uma plataforma completa de **Digital Signage / Digital Out-of-Home (DOOH)** capaz de administrar uma rede própria de telas publicitárias, com:

- controle remoto
- operação offline
- monitoramento
- campanhas
- segmentação
- relatórios
- contratos
- monetização
- expansão para centenas de dispositivos

O software deverá ser pensado desde o início para que uma nova TV possa ser adicionada à rede sem exigir alterações manuais no código.

---

# 33. Nome Provisório dos Componentes

Sugestão:

```text
signage-api
signage-admin
signage-agent
signage-player
```

Estrutura geral:

```text
digital-signage/
├── signage-api/
├── signage-admin/
├── signage-agent/
├── signage-player/
└── docs/
```

Também é possível utilizar um monorepo:

```text
digital-signage/
├── apps/
│   ├── api/
│   ├── admin/
│   ├── agent/
│   └── player/
│
├── packages/
│   ├── shared/
│   └── contracts/
│
└── docs/
```

---

# 34. Próximos Passos Técnicos

A ordem recomendada de desenvolvimento é:

```text
1. Criar Player local
2. Configurar Linux em kiosk
3. Criar Signage Agent
4. Implementar systemd
5. Implementar cache offline
6. Criar API
7. Provisionar dispositivos
8. Implementar heartbeat
9. Implementar sincronização
10. Criar painel Vue
11. Criar anunciantes
12. Criar campanhas
13. Criar regras de playlist
14. Registrar exibições
15. Criar relatórios
16. Criar financeiro
17. Criar atualização remota
```

O primeiro grande marco deverá ser:

```text
Painel
↓
alterar campanha
↓
API
↓
PC Linux detecta alteração
↓
baixa conteúdo
↓
TV passa a exibir
```

sem qualquer acesso físico ao equipamento.