{{-- Aperçu en direct des enseignes (x-data du formulaire « Décor » : v.<champ>, lines()). --}}
<div class="adms-decor" role="group" aria-labelledby="adms-decor-titre">
    <p id="adms-decor-titre" class="adm-label">Aperçu en direct</p>

    <p class="adms-decor__caption">Galerie, nuit</p>
    <div class="adms-decor__ticker adms-decor__ticker--nuit">
        <template x-for="(item, i) in lines(v.decor__bandeau_nuit)" :key="'n' + i">
            <span :class="{ 'is-over': Array.from(item).length > 40 }"><span x-text="item"></span> <span aria-hidden="true">✦</span></span>
        </template>
    </div>

    <p class="adms-decor__caption">Galerie, jour (écran LED)</p>
    <div class="adms-decor__ticker adms-decor__ticker--jour">
        <template x-for="(item, i) in lines(v.decor__bandeau_jour)" :key="'j' + i">
            <span :class="{ 'is-over': Array.from(item).length > 40 }"><span x-text="item"></span> ●</span>
        </template>
    </div>

    <div class="adms-decor__row">
        <div>
            <p class="adms-decor__caption">Illustrateurs, écran LED</p>
            <div class="adms-decor__led" :class="{ 'is-over': Array.from(v.decor__led_illustrateurs || '').length > 40 }" x-text="v.decor__led_illustrateurs"></div>
        </div>
        <div>
            <p class="adms-decor__caption">Illustrateurs, écran « drop »</p>
            <div class="adms-decor__drop" :class="{ 'is-over': Array.from(v.decor__ecran_drop || '').length > 40 }"><span class="adms-decor__dot" aria-hidden="true"></span><span x-text="v.decor__ecran_drop"></span></div>
        </div>
        <div>
            <p class="adms-decor__caption">Illustrateurs, panneau directionnel</p>
            <div class="adms-decor__dir">
                <template x-for="(item, i) in lines(v.decor__panneau_directions).slice(0, 3)" :key="'d' + i">
                    <span :class="{ 'is-over': Array.from(item).length > 40 }"><b x-text="['↑', '←', '→'][i]"></b><span x-text="item"></span></span>
                </template>
            </div>
        </div>
    </div>
    <p class="adm-help">Les phrases trop longues (plus de 40 caractères) apparaissent en rose.</p>
</div>
