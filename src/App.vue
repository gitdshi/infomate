<script>
import { ref } from 'vue'
import Chat from 'vue-material-design-icons/Chat.vue'
import Plus from 'vue-material-design-icons/Plus.vue'

import {
	NcContent,
	NcAppContent,
	NcEmptyContent,
	NcAppNavigation,
	NcAppNavigationList,
	NcAppNavigationNewItem,
	NcAppNavigationItem,
	NcButton,
	NcTextField,
	NcTextArea,
} from '@nextcloud/vue'

import { t } from '@nextcloud/l10n'

export default {
	name: 'App',

	components: {
		Chat,
		Plus,
		NcButton,
		NcTextField,
		NcTextArea,
		NcContent,
		NcAppContent,
		NcEmptyContent,
		NcAppNavigation,
		NcAppNavigationNewItem,
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
		}
	},

	methods: {
		createNewChat() {
			const newChat = {
				id: Date.now(),
				title: `Chat ${this.chatHistory.length + 1}`,
				timestamp: new Date()
			}
			this.chatHistory.push(newChat)
			this.activeChatId = newChat.id
		},
		setActiveChat(chatId) {
			this.activeChatId.value = chatId
		},
		sendMessage() {
			if (this.newMessage.trim()) {
				this.messages.push({
					id: Date.now(),
					chatId: this.activeChatId,
					content: this.newMessage,
					sender: 'user',
					timestamp: new Date()
				})
				// 这里添加AI回复逻辑
				this.newMessage.value = ''
			}
		},
		formatTime(date) {
			return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
		},
		t(key, ...args) {
			return t('infomate', key, ...args)
		}
	}
}

/*
// 计算属性
const activeMessages = computed(() =>
	messages.value.filter(msg => msg.chatId === activeChatId.value)
)
// 初始化第一个聊天
if (chatHistory.value.length === 0) {
	createNewChat()
}
*/
</script>
<template>
	<NcContent app-name="infomate" class="infomate-container">
		<NcAppNavigation>
			<NcAppNavigationNewItem name="NewChat"
				text="New Chat"
				@new-item="createNewChat"
				class="new-chat-btn">
				<template #icon>
					<Plus :size="20" />
				</template>
			</NcAppNavigationNewItem>
			<NcAppNavigationList>
				<NcAppNavigationItem
					v-for="chat in chatHistory"
					:key="chat.id"
					:name="chat.title"
					:active="activeChatId === chat.id"
					@click="setActiveChat(chat.id)"
					class="chat-history-item">
					<template #icon>
						<Chat :size="20" />
					</template>
				</NcAppNavigationItem>
			</NcAppNavigationList>

		</NcAppNavigation>

		<NcAppContent>
			<div class="infomate-content">
				<div class="message-container">
					<div v-for="message in activeMessages"
						:key="message.id"
						class="message-bubble"
						:class="`message-${message.sender}`">
						<div class="message-time">{{ formatTime(message.timestamp) }}</div>
						<div class="message-content">{{ message.content }}</div>
					</div>
				</div>

				<div class="message-input">
					<!--
					<NcTextField
						ref="inputField"
						:value.sync="newMessage"
						:placeholder="t('infomate', 'Type your message...')"
						@keydown.enter="sendMessage"
						:show-trailing-button="newMessage !== ''"
						trailing-button-label="Send"
						@trailing-button-click="sendMessage" />
					-->
					<NcTextArea label="Message"
						v-model="text1"
						:placeholder="t('infomate', 'Type your message here...')"
						helper-text="This is a regular helper text."
						@keydown.enter="sendMessage">
						<template #icon>
							<Chat :size="20" />
						</template>
					</NcTextArea>
					<NcButton></NcButton>
					<NcEmptyContent> </NcEmptyContent>
					<NcTextField></NcTextField>
				</div>
			</div>
		</NcAppContent>
	</NcContent>
</template>

<style lang="scss">
.infomate-container {
	display: flex;
	height: 100vh;

	.infomate-content {
		flex: 1;
		display: flex;
		flex-direction: column;
		padding: var(--content-padding);

		.message-container {
			flex: 1;
			overflow-y: auto;
			padding: var(--content-padding);

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

		.message-input {
			bottom: 0px;
			padding: var(--content-padding);
			border-top: 1px solid var(--color-border);
		}
	}
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
