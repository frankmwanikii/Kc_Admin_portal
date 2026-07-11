<div class="min-h-full flex items-center justify-center p-4">
    <div class="w-full max-w-md text-center">
        <p class="text-4xl mb-4">&#128591; &#10024;</p>
        <div class="w-20 h-20 rounded-full bg-[#35afe6]/20 flex items-center justify-center mx-auto mb-6 ring-4 ring-[#35afe6]/30">
            <svg class="w-10 h-10 text-[#35afe6]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold text-white">Thank you for visiting!</h1>
        <p class="text-[#35afe6] text-sm font-medium mt-2"><?= htmlspecialchars($churchName ?? 'Kingdomcity church') ?></p>
        <p class="text-white/70 mt-4 leading-relaxed">
            We've received your visitor card. Someone from our pastoral team will reach out to you at<br>
            <strong class="text-white"><?= htmlspecialchars($email) ?></strong>
        </p>
        <p class="text-white/50 text-sm mt-6">We're so glad you joined us. God bless you!</p>
        <a href="/" class="inline-flex items-center gap-2 mt-8 px-6 py-3 rounded-xl bg-[#35afe6] hover:bg-[#2da0d9] text-white font-semibold transition">
            Back to home
        </a>
    </div>
</div>
