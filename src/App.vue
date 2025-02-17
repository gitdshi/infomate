<script>
import { ref } from 'vue'
import Chat from 'vue-material-design-icons/Chat.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import {
	NcContent,
	NcAppContent,
	NcAppNavigation,
	NcAppNavigationList,
	NcAppNavigationNew,
	NcAppNavigationItem,
	NcRichContenteditable,
} from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'
import DOMPurify from 'dompurify'
import { loadMessages, streamMessages } from './services/message.js'

export default {
	name: 'App',

	components: {
		Chat,
		Plus,
		NcRichContenteditable,
		NcContent,
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationNew,
		NcAppNavigationList,
		NcAppNavigationItem,
	},

	data() {
		return {
			isSidebarCollapsed: ref(false),
			chatHistory: ref([]),
			activeChatId: ref(null),
			newMessage: ref(''),
			messages: ref([]),
		}
	},

	computed: {
		activeMessages() {
			return this.messages.filter(msg => msg.chatId === this.activeChatId)
		},
	},

	watch: {
		activeMessages() {
			this.$nextTick(() => {
				this.scrollToBottom()
			})
		},
	},

	created() {
		this.loadData()
	},

	mounted() {
		// this.createNewChat()
	},

	methods: {
		loadData() {
			setTimeout(async () => {
				try {
					const data = await loadMessages('admin', this.handleError)
					this.chatHistory = data.threads.map(thread => ({
						id: thread.slug,
						title: thread.name,
					}))

					this.messages = data.threads.flatMap(thread =>
						thread.chats.map(chatMessage => ({
							chatId: thread.slug,
							content: chatMessage.content,
							sender: chatMessage.role,
							timestamp: new Date(Number(chatMessage.sentAt)),
						})),
					)
					this.createNewChat()
					console.info(this.chatHistory)
				} catch (error) {
					this.handleError(error)
				}
			}, 5000)
		},
		createNewChat() {
			const newChat = {
				id: Date.now(),
				title: 'New Chat',
				timestamp: new Date(),
			}
			this.chatHistory.push(newChat)
			this.activeChatId = newChat.id
		},
		setActiveChat(chatId) {
			this.activeChatId = chatId
		},
		sendMessage() {
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

				this.$nextTick(() => {
					this.scrollToBottom()
				})
			}
		},
		startStreaming(question, messageAI) {
			streamMessages(question, messageAI, this.handleMessage, this.handleError)
		},
		handleMessage(chunk, messageAI) {
			// Process the chunk of data received from the stream
			if (messageAI.content === 'thinking...') {
				messageAI.content = ''
			}
			messageAI.content = messageAI.content + chunk.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br/>')

			console.info('Received chunk:', chunk)

			this.$nextTick(() => {
				this.scrollToBottom()
			})
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
						@click="setActiveChat(chat.id)">
						<template #icon>
							<Chat :size="20" />
						</template>
					</NcAppNavigationItem>
				</NcAppNavigationList>
			</NcAppNavigation>

			<NcAppContent>
				<div id="messageArea" class="infomate-content">
					<div id="messageContainer" class="message-container">
						<div v-for="message in activeMessages"
							:key="message.id"
							class="message-bubble"
							:class="`message-${message.sender}`">
							<div class="message-time">
								{{ formatTime(message.timestamp) }}
							</div>
							<div class="message-content" v-html="sanitizeContent(message.content)" />
						</div>
					</div>

					<div id="messageInput" class="message-input">
						<NcRichContenteditable
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
</style>
