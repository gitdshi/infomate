<script>
import { ref } from 'vue'
import Chat from 'vue-material-design-icons/Chat.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import Delete from 'vue-material-design-icons/Delete.vue'

import {
	NcContent,
	NcAppContent,
	NcAppNavigation,
	NcAppNavigationList,
	NcAppNavigationNew,
	NcAppNavigationItem,
	NcRichContenteditable,
	NcLoadingIcon,
} from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import DOMPurify from 'dompurify'
import { loadMessages, streamMessages } from './services/message.js'

export default {
	name: 'App',

	components: {
		Chat,
		Plus,
		Delete,
		NcRichContenteditable,
		NcContent,
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationNew,
		NcAppNavigationList,
		NcAppNavigationItem,
		NcLoadingIcon,
	},

	data() {
		return {
			isSidebarCollapsed: ref(false),
			chatHistory: ref([]),
			activeChatId: ref(null),
			newMessage: ref(''),
			messages: ref([]),
			loadingMessages: false,
			isUserScrolling: false,
			scrollTimeout: null,
		}
	},

	computed: {
		activeMessages() {
			return this.messages.filter(msg => msg.chatId === this.activeChatId)
		},
	},

	watch: {
		activeMessages() {
			if (!this.isUserScrolling) {
				this.$nextTick(() => {
					this.scrollToBottom()
				})
			}
		},
	},

	created() {
		this.loadData()
	},

	mounted() {
		const messageArea = document.getElementById('messageArea')
		messageArea.addEventListener('scroll', this.handleScroll)
	},

	beforeDestroy() {
		const messageArea = document.getElementById('messageArea')
		messageArea.removeEventListener('scroll', this.handleScroll)
	},

	methods: {
		async loadData() {
			this.loadingMessages = true
			try {
				const data = await loadMessages('admin', this.handleError)
				this.chatHistory = data.threads.map(thread => ({
					id: thread.slug,
					title: thread.name,
				}))

				this.messages = data.threads.flatMap(thread =>
					thread.chats.map(chatMessage => ({
						chatId: thread.slug,
						content: this.formatMessage(chatMessage.content),
						sender: chatMessage.role,
						timestamp: new Date(Number(chatMessage.sentAt)),
					})),
				)
				// Set the last chat history as active chat, if no history chat then create new chat
				if (this.chatHistory.length > 0) {
					this.activeChatId = this.chatHistory[this.chatHistory.length - 1].id
				} else {
					this.createNewChat()
				}
			} catch (error) {
				this.handleError(error)
			} finally {
				this.loadingMessages = false
				this.$nextTick(() => {
					this.$refs.messageInput.focus()
				})
			}
		},
		createNewChat() {
			const newChat = {
				id: Date.now(),
				title: 'New Chat',
				timestamp: new Date(),
			}
			this.chatHistory.push(newChat)
			this.activeChatId = newChat.id
			this.$nextTick(() => {
				this.$refs.messageInput.focus()
			})
		},
		setActiveChat(chatId) {
			this.activeChatId = chatId
		},
		deleteChat(chatId) {
			this.chatHistory = this.chatHistory.filter(chat => chat.id !== chatId)
			if (this.activeChatId === chatId) {
				this.activeChatId = this.chatHistory.length ? this.chatHistory[0].id : null
			}
		},
		async sendMessage() {
			if (this.newMessage.trim()) {
				this.messages.push({
					id: Date.now(),
					chatId: this.activeChatId,
					content: this.newMessage,
					sender: 'user',
					timestamp: new Date(),
				})
				const messageAI = {
					id: Date.now() + 1,
					chatId: this.activeChatId,
					content: 'thinking...',
					sender: 'assistant',
					timestamp: new Date(),
					loading: true,
				}
				this.messages.push(messageAI)

				// send the message to infomate-mind, and get the response.
				this.startStreaming(this.newMessage, messageAI)

				// Update the active chat history title if it is 'New Chat'
				const activeChat = this.chatHistory.find(chat => chat.id === this.activeChatId)
				if (activeChat && activeChat.title === 'New Chat') {
					activeChat.title = this.newMessage.substring(0, 30)
				}

				this.newMessage = ''

				if (!this.isUserScrolling) {
					this.$nextTick(() => {
						this.scrollToBottom()
					})
				}
			}
		},
		async startStreaming(question, messageAI) {
			try {
				await streamMessages(question, messageAI, this.handleMessage, this.handleError)
			} finally {
				messageAI.loading = false
			}
		},
		handleMessage(chunk, messageAI) {
			// Process the chunk of data received from the stream
			if (messageAI.content === 'thinking...') {
				messageAI.content = ''
			}
			messageAI.content = messageAI.content + this.formatMessage(chunk)

			if (!this.isUserScrolling) {
				this.$nextTick(() => {
					this.scrollToBottom()
				})
			}
		},
		formatMessage(message) {
			return message.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br/>')
		},
		handleError(error) {
			// Handle any errors that occur during the process
			console.error('Error:', error)
		},
		scrollToBottom() {
			const divMessageArea = document.getElementById('messageArea')
			divMessageArea.style.minHeight = `${document.getElementById('messageContainer').offsetHeight + document.getElementById('messageInput').offsetHeight + 50}px`
			divMessageArea.scrollIntoView(false, { behavior: 'smooth' })
		},
		handleScroll() {
			this.isUserScrolling = true
			clearTimeout(this.scrollTimeout)
			this.scrollTimeout = setTimeout(() => {
				this.isUserScrolling = false
				this.handleInactivity()
			}, 2000)

			// Check if the user has scrolled to the bottom
			const divMessageArea = document.getElementById('messageArea')
			if (divMessageArea.scrollHeight - divMessageArea.scrollTop === divMessageArea.clientHeight) {
				this.isUserScrolling = false
			}
			divMessageArea.style.minHeight = `${document.getElementById('messageContainer').offsetHeight + document.getElementById('messageInput').offsetHeight + 50}px`
		},
		handleInactivity() {
			if (!this.isUserScrolling) {
				this.$nextTick(() => {
					this.scrollToBottom()
				})
			}
		},
		formatTime(date) {
			return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
		},
		sanitizeContent(content) {
			return DOMPurify.sanitize(content)
		},
		t(key, ...args) {
			return t('infomate', key, ...args)
		},
	},
}
</script>
<template>
	<NcContent app-name="infomate">
		<div class="infomate-container">
			<NcAppNavigation>
				<NcAppNavigationNew name="newChat"
					:text="t('New Chat')"
					@click="createNewChat">
					<template #icon>
						<Plus :size="20" />
					</template>
				</NcAppNavigationNew>
				<NcAppNavigationList>
					<NcAppNavigationItem
						v-for="chat in chatHistory"
						:key="chat.id"
						:name="chat.title"
						:active="activeChatId === chat.id"
						@click="setActiveChat(chat.id)"
						@mouseover="chat.showDelete = true"
						@mouseleave="chat.showDelete = false">
						<template #icon>
							<Chat :size="20" />
						</template>
						<template #actions>
							<Delete
								v-if="chat.showDelete"
								class="delete-icon"
								:size="20"
								@click.stop="deleteChat(chat.id)" />
						</template>
					</NcAppNavigationItem>
				</NcAppNavigationList>
				<NcLoadingIcon v-if="loadingMessages"
					:size="40"
					appearance="dark"
					class="navigation-loading-icon" />
			</NcAppNavigation>

			<NcAppContent>
				<div id="messageArea" class="infomate-content">
					<div id="messageContainer" class="message-container">
						<div v-for="message in activeMessages"
							:key="message.id"
							class="message-bubble"
							:class="`message-${message.sender}`">
							<div class="message-header">
								<div class="message-time">
									{{ formatTime(message.timestamp) }}
								</div>
								<NcLoadingIcon v-if="message.loading"
									:size="20"
									appearance="dark"
									class="loading-icon" />
							</div>
							<div class="message-content" v-html="sanitizeContent(message.content)" />
						</div>
					</div>

					<div id="messageInput" class="message-input">
						<NcRichContenteditable
							ref="messageInput"
							v-model="newMessage"
							:maxlength="500"
							@keydown.enter="sendMessage" />
					</div>
				</div>
			</NcAppContent>
		</div>
	</NcContent>
</template>

<style lang="scss">
.infomate-container {
	display: flex;
	// height: 100vh;
	width: 100vw;
}
.infomate-container .infomate-content {
	flex: 1;
	flex-direction: column;
	width: 800px;
	position:relative;
	left: 0; right: 0;
	margin-left: auto; margin-right: auto;
	padding: var(--content-padding);
}

.infomate-container .infomate-content .message-container {
	overflow-y: auto;

	.message-bubble {
		max-width: 50%;
		margin: 8px 0;
		padding: 12px 16px;
		border-radius: var(--border-radius-large);

		&.message-user {
			background-color: var(--color-primary-element);
			color: var(--color-primary-text);
			margin-left: auto;
		}

		&.message-assistant {
			background-color: var(--color-background-dark);
			margin-right: auto;
		}

		.message-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.message-time {
			font-size: 0.8em;
			opacity: 0.7;
			margin-top: 4px;
		}
	}
}

.infomate-container .infomate-content .message-input {
	width: 800px;
	position: fixed;
	bottom: 10px;
	border-top: 1px solid var(--color-border);
}

.infomate-container .navigation-loading-icon {
  margin-left: auto;
  margin-right: 10px;
}

.loading-icon {
  margin-left: 10px;
}

.delete-icon {
  color: white;
  display: none;
}

.chat-item:hover .delete-icon {
  display: block;
}
</style>
