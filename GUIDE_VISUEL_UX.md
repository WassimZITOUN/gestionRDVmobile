# 🎨 Guide Visuel des Améliorations UX

## 1. 📋 Flux de Prise de Rendez-vous

### Étape 1: Nouvelle Prise de RDV
```
┌─────────────────────────────────────────────┐
│  Prendre un rendez-vous                     │
├─────────────────────────────────────────────┤
│                                             │
│  ╔════════════════════════════════════╗    │
│  ║ 📍 Étape 1 sur 2                   ║    │
│  ║ Sélectionnez une date et un        ║    │
│  ║ médecin pour voir les créneaux     ║    │
│  ╚════════════════════════════════════╝    │
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │ Date du rendez-vous                 │   │
│  │ [____________________]              │   │
│  │ Veuillez sélectionner une date...   │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │ Médecin                             │   │
│  │ [____________________]              │   │
│  │ Sélectionnez le médecin que vous... │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  [ → Voir les créneaux disponibles ] [X]   │
│                                             │
└─────────────────────────────────────────────┘
```

### Étape 2: Sélection du Créneau
```
┌─────────────────────────────────────────────┐
│  Sélection du créneau horaire               │
├─────────────────────────────────────────────┤
│                                             │
│  ╔════════════════════════════════════╗    │
│  ║ Récapitulatif                      ║    │
│  ║ ┌──────────────┬──────────────┐   ║    │
│  ║ │ 23/12/2025   │ Dr. Durand   │   ║    │
│  ║ └──────────────┴──────────────┘   ║    │
│  ╚════════════════════════════════════╝    │
│                                             │
│  Choisissez un créneau :                   │
│                                             │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐      │
│  │ 09:00   │ │ 10:00   │ │ 11:00   │      │
│  └─────────┘ └─────────┘ └─────────┘      │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐      │
│  │ 13:00   │ │ 14:00   │ │ 15:00   │      │
│  └─────────┘ └─────────┘ └─────────┘      │
│                                             │
│  [ ✓ Confirmer le créneau ] [ Annuler ]   │
│                                             │
└─────────────────────────────────────────────┘
```

---

## 2. 📝 Flux de Modification

```
┌─────────────────────────────────────────────┐
│  Modifier le rendez-vous                    │
├─────────────────────────────────────────────┤
│  ℹ️ Modification en cours                   │
│  Modifier un rendez-vous implique une       │
│  nouvelle demande de confirmation...        │
│                                             │
│  ╔════════════════════════════════════╗    │
│  ║ Rendez-vous actuel                 ║    │
│  ║ Date: 23/12/2025                   ║    │
│  ║ Heure: 14:00 – 15:00               ║    │
│  ║ Médecin: Dr Durand                 ║    │
│  ║ État: ✓ Confirmé                   ║    │
│  ╚════════════════════════════════════╝    │
│                                             │
│  Nouvelles informations                    │
│  ┌─────────────────────────────────────┐   │
│  │ Date du rendez-vous                 │   │
│  │ [____________________]              │   │
│  └─────────────────────────────────────┘   │
│  ┌─────────────────────────────────────┐   │
│  │ Médecin                             │   │
│  │ [____________________]              │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  [ → Voir les créneaux ] [ Annuler ]       │
│                                             │
└─────────────────────────────────────────────┘
```

---

## 3. 👁️ Vue Détail du Rendez-vous

```
┌─────────────────────────────────────────────┐
│  Détail du rendez-vous                      │
│  État : ✓ CONFIRMÉ                          │
├─────────────────────────────────────────────┤
│                                             │
│  ┌──────────────────┬──────────────────┐   │
│  │ 📅 Date          │ 14:00 – 15:00   │   │
│  │ 23/12/2025       │ (Horaire)       │   │
│  │                  │                 │   │
│  │ 👨‍⚕️ Médecin        │ 👤 Patient       │   │
│  │ Dr Durand        │ Jean Dupont     │   │
│  └──────────────────┴──────────────────┘   │
│                                             │
│  ╔════════════════════════════════════╗    │
│  ║ Actions disponibles                ║    │
│  ║ [✏️ Modifier] [❌ Annuler] [X]      ║    │
│  ╚════════════════════════════════════╝    │
│                                             │
│  ← Retour à mes rendez-vous                │
│                                             │
└─────────────────────────────────────────────┘
```

---

## 4. 📊 Liste des Rendez-vous

```
┌─────────────────────────────────────────────┐
│  Mes rendez-vous        [ ➕ Prendre un RDV ]
├─────────────────────────────────────────────┤
│                                             │
│  Filtres:                                   │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐   │
│  │ État: ▼  │ │ Période: │ │ [Rech.] │   │
│  └──────────┘ └──────────┘ └──────────┘   │
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │ 23    14:00 – 15:00                 │   │
│  │ 12    Dr Durand          CONFIRMÉ   │   │
│  │                                      │   │
│  │              [📋 Détails] [X Refus]  │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │ 24    09:00 – 10:00                 │   │
│  │ 12    Dr Martel           DEMANDÉ   │   │
│  │                                      │   │
│  │              [📋 Détails] [X Refus]  │   │
│  └─────────────────────────────────────┘   │
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │ 25    10:00 – 11:00                 │   │
│  │ 12    Dr Benoit           ANNULÉ    │   │
│  │                                      │   │
│  │              [👁️ Voir]               │   │
│  └─────────────────────────────────────┘   │
│                                             │
└─────────────────────────────────────────────┘
```

---

## 5. 🎨 Codes de Couleur par État

```
┌──────────────┬──────────┬──────────────────┐
│ État         │ Couleur  │ Signification    │
├──────────────┼──────────┼──────────────────┤
│ DEMANDÉ      │ 🔵 Bleu  │ En attente       │
│ CONFIRMÉ     │ 🟢 Vert  │ Approuvé         │
│ ANNULÉ       │ 🔴 Rouge │ Supprimé         │
│ REFUSÉ       │ 🔴 Rouge │ Rejeté           │
│ RÉALISÉ      │ ⚫ Gris   │ Complété         │
└──────────────┴──────────┴──────────────────┘
```

---

## 6. 🔘 États des Boutons

### Créneau Normal (Hover)
```
┌─────────────┐        ┌─────────────┐
│   09:00     │  Hover │   09:00     │
└─────────────┘        └─────────────┘
Bordure grise          Bordure bleue
Texte bleu            Fond bleu
                       Texte blanc
                       Ombre
```

### Bouton Principal
```
[ → Voir les créneaux disponibles ]
Bleu primaire avec texte blanc
Hover: Légèrement surélevé + ombre
```

### Bouton Secondaire
```
[ Annuler ]
Gris avec texte blanc
Hover: Transition fluide
```

---

## 7. 📱 Responsive (Mobile)

### Petit écran:
```
┌──────────────┐
│ RDV          │
│ 23/12/2025   │
│ 14:00 - 15   │
│ Dr Durand    │
│ CONFIRMÉ     │
│              │
│ [Détails]    │
│ [Refuser]    │
└──────────────┘

Cartes empilées verticalement
Boutons en colonne
Filtres responsive
```

---

## 8. 🎯 Hiérarchie Visuelle

### Importance:
```
TRÈS IMPORTANT
├─ Titres h1 (font-size: 1.75rem)
├─ Boutons d'action primaires
└─ Badges d'état

IMPORTANT
├─ Date/Heure (grands textes)
├─ Noms (médecin, patient)
└─ Récapitulatifs

NORMAL
├─ Textes d'aide
├─ Labels des formulaires
└─ Textes secondaires

MOINS IMPORTANT
└─ Textes mutés
```

---

## 9. ✨ Effets de Transitions

### Focus/Hover:
```
300ms transition fluide
├─ Bordure change de couleur
├─ Ombre augmente
├─ Texte reste stable
└─ Transform translateY(-2px)
```

### État Actif (Click):
```
Bouton enfoncé
└─ Transform translateY(0)
```

---

## 10. 📐 Espacements

### Standardisés:
```
--space-xs:  6px    (très petit)
--space-sm: 12px    (petit)
--space-md: 20px    (normal)
--space-lg: 36px    (large)
```

### Dans les composants:
```
Formulaire:
├─ Padding: 20-24px
├─ Gap entre champs: 20px
└─ Gap entre boutons: 12px

Cartes:
├─ Padding: 20px
├─ Border-radius: 8px
└─ Ombre: var(--shadow-sm)
```

---

## 11. 💡 Principes Appliqués

✅ **Clarté** : Informations toujours visibles
✅ **Cohérence** : Design unifié
✅ **Feedback** : États visuels pour chaque action
✅ **Guidage** : Étapes claires et balisées
✅ **Accessibilité** : Labels, couleurs, contraste
✅ **Responsivité** : Tous les appareils supportés
✅ **Performance** : Transitions rapides (180ms)

---

**Créé le:** 23 décembre 2025  
**Version:** 1.0
