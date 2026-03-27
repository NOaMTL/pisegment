<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Générateur PDF - Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-6 text-gray-800">Générateur de PDF</h1>
            
            <!-- Démo rapide -->
            <div class="mb-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h2 class="text-xl font-semibold mb-3 text-blue-800">🚀 Test Rapide</h2>
                <p class="text-gray-700 mb-4">Générer un PDF de démonstration avec 50 clients</p>
                <a href="/api/generate-pdf/demo" target="_blank" 
                   class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                    Télécharger PDF Démo
                </a>
            </div>

            <!-- Formulaire personnalisé -->
            <div class="bg-gray-50 p-6 rounded-lg">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">📝 Génération Personnalisée</h2>
                
                <form id="pdfForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Requête SQL *
                        </label>
                        <textarea 
                            id="sql" 
                            rows="4" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="SELECT * FROM customers LIMIT 20"
                        >SELECT id, first_name, last_name, email, city, average_balance FROM customers LIMIT 30</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Titre du document
                        </label>
                        <input 
                            type="text" 
                            id="title"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Rapport Clients"
                            value="Export des Clients"
                        />
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Header Gauche
                            </label>
                            <input 
                                type="text" 
                                id="headerLeft"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                value="Ma Société"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Header Centre
                            </label>
                            <input 
                                type="text" 
                                id="headerCenter"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                value="27/03/2026"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Header Droite
                            </label>
                            <input 
                                type="text" 
                                id="headerRight"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                value="Confidentiel"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Orientation
                        </label>
                        <select 
                            id="orientation"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="P">Portrait</option>
                            <option value="L" selected>Paysage (Landscape)</option>
                        </select>
                    </div>

                    <div class="pt-4">
                        <button 
                            type="submit"
                            class="w-full bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition font-semibold"
                        >
                            📄 Générer le PDF
                        </button>
                    </div>
                </form>

                <div id="status" class="mt-4 text-center text-sm"></div>
            </div>

            <!-- Documentation -->
            <div class="mt-8 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <h3 class="font-semibold text-gray-800 mb-2">📚 Documentation</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Consultez <code class="bg-gray-200 px-2 py-1 rounded">README_PDF_GENERATOR.md</code> pour plus d'exemples</li>
                    <li>• API Endpoint : <code class="bg-gray-200 px-2 py-1 rounded">POST /api/generate-pdf</code></li>
                    <li>• Démo : <code class="bg-gray-200 px-2 py-1 rounded">GET /api/generate-pdf/demo</code></li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('pdfForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const status = document.getElementById('status');
            status.innerHTML = '<span class="text-blue-600">⏳ Génération du PDF en cours...</span>';
            
            try {
                const response = await fetch('/api/generate-pdf', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        sql: document.getElementById('sql').value,
                        title: document.getElementById('title').value,
                        headerLeft: document.getElementById('headerLeft').value,
                        headerCenter: document.getElementById('headerCenter').value,
                        headerRight: document.getElementById('headerRight').value,
                        orientation: document.getElementById('orientation').value
                    })
                });
                
                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Erreur lors de la génération');
                }
                
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'rapport_' + new Date().getTime() + '.pdf';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                status.innerHTML = '<span class="text-green-600">✅ PDF généré avec succès !</span>';
                
                setTimeout(() => {
                    status.innerHTML = '';
                }, 3000);
                
            } catch (error) {
                status.innerHTML = '<span class="text-red-600">❌ Erreur : ' + error.message + '</span>';
            }
        });
    </script>
</body>
</html>
