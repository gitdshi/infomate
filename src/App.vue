<script>
import { ref } from 'vue'
import Chat from 'vue-material-design-icons/Chat.vue'
import Plus from 'vue-material-design-icons/Plus.vue'

import {
	NcContent,
	NcAppContent,
	// NcEmptyContent,
	NcAppNavigation,
	NcAppNavigationList,
	NcAppNavigationNew,
	NcAppNavigationItem,
	NcRichContenteditable,
	// NcButton,
	// NcTextField,
	// NcTextArea,
} from '@nextcloud/vue'

import { t } from '@nextcloud/l10n'

export default {
	name: 'App',

	components: {
		Chat,
		Plus,
		// NcButton,
		// NcTextField,
		NcRichContenteditable,
		// NcTextArea,
		NcContent,
		NcAppContent,
		// NcEmptyContent,
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
				this.adjustMessageArea()
			})
		},
	},

	mounted() {
		this.createNewChat()
	},

	methods: {
		createNewChat() {
			const newChat = {
				id: Date.now(),
				title: `Chat ${this.chatHistory.length + 1}`,
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
				this.messages.push({
					id: Date.now() + 1,
					chatId: this.activeChatId,
					content: this.newMessage,
					sender: 'assistant',
					timestamp: new Date(),
				})
				// 这里添加AI回复逻辑
				this.newMessage = ''
			}
		},
		adjustMessageArea() {
			const divMessageArea = document.getElementById('messageArea')
			divMessageArea.style.minHeight = `${document.getElementById('messageContainer').offsetHeight + document.getElementById('messageInput').offsetHeight + 50}px`
			divMessageArea.scrollIntoView(false, { behavior: 'smooth' })
		},
		formatTime(date) {
			return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
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
							<div class="message-content">
								{{ message.content }}
							</div>
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
	<!--
	<NcTextField
		ref="inputField"
		:value.sync="newMessage"
		:placeholder="t('infomate', 'Type your message...')"
		:show-trailing-button="newMessage !== ''"
		trailing-button-icon="arrowRight"
		trailing-button-label="Send"
		@keydown.enter="sendMessage"
		@trailing-button-click="sendMessage" />
	<NcTextArea label="Message"
		v-model="text1"
		:placeholder="t('infomate', 'Type your message here...')"
		helper-text="This is a regular helper text."
		@keydown.enter="sendMessage">
		<template #icon>
			<Chat :size="20" />
		</template>
	</NcTextArea>
	-->
	<!--
	<NcButton></NcButton>
	<NcEmptyContent> </NcEmptyContent>
	<NcTextField></NcTextField>
	-->
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

.new-chat-btn {
	width: 100%;
	justify-content: center;
	margin: 8px 0;
}

.chat-history-item {
	&:hover {
		background-color: var(--color-background-hover);
	}

	&.active {
    background-color: var(--color-primary-element-light) !important;
	}
}
</style>
