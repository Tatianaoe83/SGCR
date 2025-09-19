export default {
  name: 'ChatbotInterface',
  data() {
      return {
          messages: [],
          currentMessage: '',
          isLoading: false,
          sessionId: this.generateSessionId(),
          typingIndicator: false,
          analytics: {
              totalQueries: 0,
              cacheHitRate: 0,
              averageResponseTime: 0
          },
          suggestions: [
              '¿Cuáles son sus horarios de atención?',
              '¿Qué servicios ofrecen?',
              '¿Cuál es su ubicación?',
              '¿Tienen productos disponibles?'
          ]
      }
  },
  methods: {
      async sendMessage() {
          if (!this.currentMessage.trim() || this.isLoading) return;
          
          const userMessage = this.currentMessage.trim();
          this.addMessage('user', userMessage);
          this.currentMessage = '';
          this.isLoading = true;
          this.typingIndicator = true;
          
          try {
              const response = await fetch('/api/chatbot/query', {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                      'Authorization': `Bearer ${this.$auth.token}`
                  },
                  body: JSON.stringify({
                      message: userMessage,
                      session_id: this.sessionId
                  })
              });
              
              if (response.status === 429) {
                  const errorData = await response.json();
                  this.addMessage('system', errorData.message, { 
                      isError: true, 
                      retryAfter: errorData.retry_after 
                  });
                  return;
              }
              
              const data = await response.json();
              
              // Simular typing delay para mejor UX
              setTimeout(() => {
                  this.addMessage('bot', data.response, {
                      method: data.method,
                      responseTime: data.response_time_ms,
                      cached: data.cached,
                      sources: data.sources,
                      analyticsId: data.analytics_id
                  });
                  
                  this.updateAnalytics(data);
                  this.typingIndicator = false;
              }, this.getTypingDelay(data.response));
              
          } catch (error) {
              console.error('Chatbot error:', error);
              setTimeout(() => {
                  this.addMessage('bot', 'Lo siento, hubo un error de conexión. Por favor intenta nuevamente.', {
                      isError: true
                  });
                  this.typingIndicator = false;
              }, 1000);
          } finally {
              this.isLoading = false;
          }
      },
      
      addMessage(sender, content, meta = {}) {
          this.messages.push({
              id: Date.now(),
              sender,
              content,
              timestamp: new Date(),
              ...meta
          });
          
          this.$nextTick(() => {
              this.scrollToBottom();
          });
      },
      
      async sendFeedback(messageId, helpful, comment = null) {
          const message = this.messages.find(m => m.id === messageId);
          if (!message || !message.analyticsId) return;
          
          try {
              await fetch('/api/chatbot/feedback', {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                      'Authorization': `Bearer ${this.$auth.token}`
                  },
                  body: JSON.stringify({
                      analytics_id: message.analyticsId,
                      helpful: helpful,
                      comment: comment
                  })
              });
              
              // Marcar mensaje como con feedback enviado
              message.feedbackSent = true;
              message.userFeedback = helpful;
              
          } catch (error) {
              console.error('Feedback error:', error);
          }
      },
      
      useSuggestion(suggestion) {
          this.currentMessage = suggestion;
          this.sendMessage();
      },
      
      updateAnalytics(data) {
          this.analytics.totalQueries++;
          
          // Actualizar cache hit rate
          const cachedQueries = this.messages.filter(m => 
              m.sender === 'bot' && m.cached
          ).length;
          this.analytics.cacheHitRate = 
              Math.round((cachedQueries / this.analytics.totalQueries) * 100);
          
          // Actualizar tiempo promedio
          const totalTime = this.messages
              .filter(m => m.sender === 'bot' && m.responseTime)
              .reduce((sum, m) => sum + m.responseTime, 0);
          this.analytics.averageResponseTime = 
              Math.round(totalTime / this.analytics.totalQueries);
      },
      
      getTypingDelay(response) {
          // Simular tiempo de escritura realista
          const baseDelay = 1000;
          const charDelay = response.length * 20; // 20ms por carácter
          return Math.min(baseDelay + charDelay, 3000); // Máximo 3 segundos
      },
      
      generateSessionId() {
          return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
      },
      
      scrollToBottom() {
          const container = this.$refs.messagesContainer;
          if (container) {
              container.scrollTop = container.scrollHeight;
          }
      },
      
      formatTimestamp(date) {
          return date.toLocaleTimeString('es-ES', {
              hour: '2-digit',
              minute: '2-digit'
          });
      },
      
      getMethodBadgeColor(method) {
          return {
              'smart_index': 'bg-green-100 text-green-800',
              'ollama': 'bg-blue-100 text-blue-800',
              'fallback': 'bg-red-100 text-red-800'
          }[method] || 'bg-gray-100 text-gray-800';
      },
      
      getMethodText(method) {
          return {
              'smart_index': 'Respuesta Rápida',
              'ollama': 'IA Generativa',
              'fallback': 'Sistema'
          }[method] || method;
      }
  },
  
  mounted() {
      // Auto-focus en el input
      this.$refs.messageInput?.focus();
      
      // Cargar analytics si está autenticado
      if (this.$auth.user) {
          this.loadAnalytics();
      }
  }
}