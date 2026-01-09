# Configuração de IA - Groq API

## 🚀 Como obter sua API Key do Groq (GRÁTIS)

### Passo 1: Criar conta
1. Acesse https://console.groq.com
2. Clique em "Sign Up" (pode usar conta Google/GitHub)
3. Confirme seu email

### Passo 2: Gerar API Key
1. Após login, vá em https://console.groq.com/keys
2. Clique em "Create API Key"
3. Dê um nome (ex: "API Sentinel")
4. Copie a chave (começa com `gsk_...`)

### Passo 3: Configurar no projeto
```bash
# No arquivo .env adicione:
GROQ_API_KEY=gsk_sua_chave_aqui
```

## 📊 Limites do Plano Gratuito

- ✅ **6,000 requests por dia**
- ✅ **100 requests por minuto**
- ✅ **Modelos disponíveis:**
  - Llama 3.1 70B (recomendado para qualidade)
  - Llama 3.1 8B (recomendado para velocidade)
  - Mixtral 8x7B
  - Gemma 2 9B

## 🧪 Testar a integração

```bash
# Dentro do container
docker compose exec app php artisan tinker

# No tinker:
$client = app(\App\Services\LLM\GroqClient::class);
$response = $client->chat([
    ['role' => 'user', 'content' => 'Hello! Say hi in Portuguese.']
]);
echo $response['content'];
```

## 🔄 Alternativas

### OpenAI (Pago, melhor qualidade)
```env
OPENAI_API_KEY=sk-...
```

### Ollama (Local, gratuito, requer GPU)
```bash
# Instalar Ollama
curl -fsSL https://ollama.com/install.sh | sh

# Baixar modelo
ollama pull llama3

# Rodar
ollama serve
```

## 📝 Modelos Recomendados

| Modelo | Uso | Velocidade | Qualidade |
|--------|-----|------------|-----------|
| llama-3.1-70b-versatile | Produção | Média | Alta ⭐ |
| llama-3.1-8b-instant | Desenvolvimento | Muito Alta | Boa |
| mixtral-8x7b-32768 | Contexto grande | Média | Alta |

## ⚠️ Boas Práticas

1. **Nunca commite** sua API key
2. **Use rate limiting** nas chamadas
3. **Cache** respostas quando possível
4. **Monitore** uso de tokens
5. **Tenha fallback** quando LLM falhar

## 🔒 Segurança

```env
# .env.example (commitar)
GROQ_API_KEY=

# .env (NÃO commitar)
GROQ_API_KEY=gsk_real_key_here
```

Adicione no `.gitignore`:
```
.env
.env.local
```

## 📚 Documentação

- Groq: https://console.groq.com/docs
- Modelos: https://console.groq.com/docs/models
- Rate Limits: https://console.groq.com/docs/rate-limits

