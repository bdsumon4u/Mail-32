<x-guest-layout>
    <header class="px-5 pb-10 text-gray-200 bg-gray-800">
        <div class="max-w-5xl px-2 py-5 mx-auto text-4xl italic font-bold ff-iceland">
            <div class="flex justify-between">
                <div>Verify 32</div>
                <div>Premium</div>
            </div>
        </div>
        <div class="grid w-auto h-16 mx-auto my-4 italic font-bold text-gray-800 bg-gray-200 border place-content-center sm:w-80">
            Advertisement
        </div>
        <div class="flex flex-wrap items-center justify-center max-w-6xl gap-5 mx-auto">
            <div class="hidden font-bold text-gray-800 bg-gray-200 xl:grid w-60 h-60 place-content-center">Advertisement</div>
            <div class="flex-1 p-2 border-2 border-gray-500 border-dashed md:p-5">
                <div class="text-xl italic font-bold text-center">Your Temporary Email Address</div>
                <x-splade-form action="/" default="{email: '{{ $tempMail->address }}'}" class="mt-4">
                    <div class="flex flex-wrap justify-center gap-x-3 gap-y-2">
                        <x-splade-select @change="switchMail" name="email" :options="$list" class="text-gray-600 rounded-md w-60 sm:w-72 md:w-80 lg:w-96" choices="{searchEnabled: false}" />
                        <div class="flex justify-center gap-x-3">
                            <button class="px-2 py-2 bg-gray-500 rounded-md">QR</button>
                            <button class="px-2 py-2 bg-gray-500 rounded-md">Copy</button>
                        </div>
                    </div>
                    <div class="flex flex-wrap justify-center gap-3 mt-2">
                        <Link href="{{ route('temp-mail.new') }}" class="px-2 py-2 bg-gray-500 rounded-md">New</Link>
                        <Link href="{{ route('temp-mail.change') }}" class="px-2 py-2 bg-gray-500 rounded-md">Change</Link>
                        <button class="px-2 py-2 bg-gray-500 rounded-md">Refresh</button>
                        <button class="px-2 py-2 bg-gray-500 rounded-md">Delete</button>
                    </div>
                </x-splade-form>
                <div class="px-4 pt-4 mt-4 text-center border-t-2 border-gray-500 border-dashed">
                    <p class="italic font-bold text-gray-400 text-md">Forget about spam, advertising mailings, hacking and attacking robots. Keep your real mailbox clean and secure. Temp Mail provides temporary, secure, anonymous, free, disposable email address.</p>
                </div>
            </div>
            <div class="grid font-bold text-gray-800 bg-gray-200 w-60 h-60 place-content-center">Advertisement</div>
        </div>
        <div class="flex flex-wrap justify-center max-w-3xl gap-5 mx-auto mt-5 text-2xl italic font-semibold text-center text-gray-300 whitespace-nowrap ff-iceland">
            <div class="flex-1 px-4 py-2 bg-gray-700 border-2 border-gray-500 border-dashed rounded-md shadow-sm">
                <p>Email Created</p>
                <p>468451</p>
            </div>
            <div class="flex-1 px-4 py-2 bg-gray-700 border-2 border-gray-500 border-dashed rounded-md shadow-sm">
                <p>Email Received</p>
                <p>4665164</p>
            </div>
            <div class="flex-1 px-4 py-2 bg-gray-700 border-2 border-gray-500 border-dashed rounded-md shadow-sm">
                <p>Email Sent</p>
                <p>4545</p>
            </div>
        </div>
    </header>
    <section class="px-2 py-10 bg-gray-100 md:px-5">
        <div class="flex flex-wrap justify-center gap-5 mx-auto">
            <div class="w-96">
                <header class="flex justify-between px-4 py-4 italic font-semibold text-white bg-gray-800 rounded-md gap-x-2">
                    <div>Received Emails</div>
                </header>
                <body>
                    <ul>
                        @foreach ($tempMail->emailAccount->folders as $_folder)
                        <li class="p-2 my-2 bg-gray-200 rounded-md shadow-sm cursor-pointer group hover:bg-gray-300">
                            <Link href="{{ route('temp-mail', ['folder' => $_folder]) }}" class="font-semibold group-hover:underline">{{ $_folder->display_name }}</Link>
                        </li>
                        @endforeach
                    </ul>
                </body>
            </div>
            <div class="flex-1 max-w-3xl">
                <div class="flex flex-col h-full">
                    <div class="grid w-auto h-32 max-w-3xl mb-3 font-bold text-gray-800 bg-gray-200 place-content-center">Advertisement</div>
                    <header class="flex justify-between px-4 py-4 italic font-semibold text-white bg-gray-800 rounded-md gap-x-2">
                        <div>Sender</div>
                        <div>Subject</div>
                        <div>View</div>
                    </header>
                    <body class="flex-1 p-2 mt-3 min-h-[30rem] h-auto bg-white rounded-md shadow-sm">
                        @if ($message->exists)
                        <iframe class="w-full h-full" srcdoc="{{ $message->html_body ?? $message->text_body }}" frameborder="0"></iframe>
                        @elseif ($messages?->isNotEmpty())
                            <ul>
                                @foreach ($messages as $message)
                                <li class="p-2 my-2 bg-gray-200 rounded-md shadow-sm cursor-pointer group hover:bg-gray-300">
                                    <p class="font-semibold group-hover:underline">{{ $message->subject }} {{ $message->subject }}</p>
                                    <div class="my-2 ml-2 truncate">
                                        @if ($name = $message->from->name)
                                        <p class="text-gray-500">{{ $name }}</p>
                                        @endif
                                        <p class="text-gray-500">{{ $message->from->address }}</p>
                                    </div>
                                    <p class="text-xs italic font-semibold text-gray-500">{{ $message->created_at->format('d-M-Y H:i A') }}</p>
                                </li>
                                @endforeach
                            </ul>
                            {{ $messages?->links() }}
                        @else
                        <p class="text-red-500">No messages in {{ $folder->display_name }} folder.</p>
                        @endif
                    </body>
                    <div class="grid w-auto h-32 max-w-3xl mt-3 font-bold text-gray-800 bg-gray-200 place-content-center">Advertisement</div>
                </div>
            </div>
            <div class="grid italic font-bold text-gray-800 bg-gray-200 place-content-center w-36">Adv</div>
        </div>
    </section>
    <section class="h-20 text-gray-900 bg-gray-100 diagonal-stripes bg-dots"></section>
    <section class="px-4 mt-10">
        <div class="max-w-5xl mx-auto">
            <div class="text-2xl text-gray-600 ff-iceland">
                <div class="mb-5">
                    <h2 class="mb-2 text-4xl font-bold text-gray-900">The Tech behind Disposable Email Addresses</h2>
                    <p>Everyone owns an email address each and every hour, for everything from connecting at work, with business prospects, reaching out to friends and colleagues using the email address as an online passport. Nearly 99% of all apps and services we sign-up today required an email address, likewise to most shoppers loyalty cards, contest and offer entries, and more.<br><br>We all enjoy having an email address, but getting tons of spam emails each day doesn’t feel comfortable. Furthermore, it’s entirely common for stores to have their databases hacked, leaving your business email address at risk and more likely to end up on spam lists. Still, nothing done online is 100% private. Thus you need to protect your email contact identity and best done using disposable emails address.</p>
                </div>
                <div class="mb-5">
                    <h2 class="mb-2 text-4xl font-bold text-gray-900">So, What Is A Disposable Email Address?</h2>
                    <p>Recently, I found a bounce rate complex than usual on my latest email blast! I later realized the surge of users (or bots) signing up for my services hiding their real identity using disposable mail addresses.</p>
                    <p>Disposable email address (DEA) technically means an approach where a user’s with a unique email address gets a temporary email address for your current contact. The DEA allow the creation of an email address that passes validity need to sign-up for services and website without having to show your true identity.<br><br>Disposable emails address if compromised or used in connection with email abuse online, the owner can’t be tied to the abuse and quickly cancel its application without affecting other contacts. With temporary mail, you can you receive your emails from the fake emails in your genuine emails address for a specified time set. The fake email address is simply a through-away email, temporary email set and self-destructs email.<br><br></p>
                </div>
                <div class="mb-5">
                    <h2 class="mb-2 text-4xl font-bold text-gray-900">Why would you need a fake email address?</h2>
                    <p>You must have noted services such as Amazon Prime, Hulu and Netflix allow limited-time test runs(trials), however, if still determined to use the services all you need is a disposable email address. Technically, you can extend your trial usage using a different email address linked to your original (genuine) after the trial period expires.<br><br>An offline or online retailer tend to demand an email address to take advantage of their offers, however, this result in an unwanted deluge of spam promotional emails that you could avoid.&nbsp;Temporary email address makes it easy to cut out those irritating messages you are still receiving.<br><br>Technically, the idea of a temporary email address conjures up with black hat hackers and underworld internet, but there are convincing reason to us fake email services.<br><br>If you are looking for legitimate reasons to use a disposable email address here’s a few:</p>
                    <ul>
                        <li><strong>Sign-Up For Store Loyalty Card:</strong><br>If you don’t want to get promotional emails from the store adverting new products, use a disposable email address instead of your business email address, and you rule out spam emails. If the store gets hacked for email, you real email address won’t get stolen.</li>
                        <li><strong>Test Your App:</strong><br>You just completed coding a web app, and you want to test it comprehensively before releasing it for sale, you can easily get 100 disposable emails, create dummy accounts and test it yourself other than hiring unreliable users online to test the app.</li>
                        <li><strong>Sign-Up For Double Account With A Web App:</strong><br>You need another IFTTT account to program a second Twitter account run for your marketing site. A new account needs a different mail from your default, to rule out managing a new email inbox, get a new disposable email address at temp-mail.org</li>
                        <li><strong>Eliminate Spam:</strong><br>A Disposable email address is a very useful tool against spam, especially, for users who consistently access web forms, forums and discussion groups you can curb spam to an absolute minimum with a disposable email address.</li>
                    </ul>
                </div>
                <div class="mb-5">
                    <h2 class="mb-2 text-4xl font-bold text-gray-900">How to Choose a Disposable Email?</h2>
                    <p>The best fake email provider should:</p>
                    <ul>
                        <li>Allow users create temporary emails address at the click of a button.</li>
                        <li>No registration is registration or identity information about the user.</li>
                        <li>The email address should remain anonymous.</li>
                        <li>Offer more than one email address (as many as you may want).</li>
                        <li>Offers temporarily email stored (temporal email inbox at user’s disposal).</li>
                        <li>Straightforward and functional design to get a mundane email.</li>
                        <li>Provider random account and users can choose an address of choice.</li>
                    </ul>
                    <p>Thus stay spam free and save time with temp-mail.org your favorite email service.</p>
                </div>
                <div class="mb-5">
                    <h2 class="mb-2 text-4xl font-bold text-gray-900">How to Use Disposable Email Address?</h2>
                    <p>Users choose to get disposable email address by creating a new email account with their current email provider’s such as Gmail, but the account comes with many challenges such as you will have to manage emails new account. Users, who opt for free mail services by creating a new account, put up with a new email address.<br><br>It’d work if you had one email address and a few disposable emails from temp-mail.org and managed one account inbox.<br><br>The amazing thing about a disposable email address is you can forward directly to your real email account. In case the disposable email address is compromised, and you are suspicious of one of your contacts you can have those emails sent directly to your trash, and for those necessary connections have them sent directly to your real email address inbox.</p>
                </div>
                <div class="mb-5">
                    <h2 class="mb-2 text-4xl font-bold text-gray-900">To Conclude:</h2>
                    <p>Have a disposable mail address system set up in a fantastic way to make sure when you participate in online wikis, chat rooms, and file sharing services and bulletin boards forums your real identity is never disclosed and never sold to anyone to avoid mail spam with Temp-mail.org.</p>
                </div>
            </div>
        </div>
    </section>
    <footer class="px-5 pb-10 text-gray-200 bg-gray-800">
        <div class="max-w-5xl px-2 py-5 mx-auto text-4xl italic font-bold ff-iceland">
            <div class="flex justify-between">
                <div>Verify 32</div>
                <div>Premium</div>
            </div>
        </div>
    </footer>
</x-guest-layout>
