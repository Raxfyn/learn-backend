<x-mail::message>
    # 你好，{{ $user->name }}

    感谢注册，你的邮箱是 {{ $user->email }}。

    <x-mail::button :url="'http://127.0.0.1:8000/api/users'">
        看看用户列表
    </x-mail::button>

    祝好
</x-mail::message>