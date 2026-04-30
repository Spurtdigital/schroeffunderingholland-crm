<div class="p-4">
    <div class="mb-4">
        <h3 class="text-lg font-semibold mb-2">Onderwerp:</h3>
        <div class="p-3 bg-gray-100 rounded">
            {{ $template->subject }}
        </div>
    </div>
    
    <div>
        <h3 class="text-lg font-semibold mb-2">Inhoud:</h3>
        <div class="p-4 bg-gray-100 rounded whitespace-pre-wrap">
            {{ $template->content }}
        </div>
    </div>
    
    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded">
        <p class="text-sm text-blue-800">
            <strong>Let op:</strong> Dit is een voorbeeld met de placeholder tekst. 
            In de daadwerkelijke email worden de variabelen zoals @{{klant_naam}} vervangen door echte waarden.
        </p>
    </div>
</div>
