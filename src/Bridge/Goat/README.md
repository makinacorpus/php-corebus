# Intégration de makinacorpus/goat

Permet d'utiliser `makinacorpus/goat`, qui implémente une variante plus ancienne
et plus générique du code de ce paquet, de façon transparente.

En gros, on redéfini quelques services de `makinacorpus/goat` qui a le bon goût
d'utiliser extensivement des interfaces, ce qui rend l'opération facile, et on
proxifie ce qui lui est envoyé dans notre bus.

Le but est de pouvoir utiliser les anciens bundles existants qui utilisent déjà
makinacorpus/goat de façon transparente pour pouvoir bootstrapper le projet
beaucoup plus rapidement.

Afin d'activer cette couche de compatibilité, veillez à renseigner dans le
fichier `config/packages/goat.yaml`:

```yaml
goat:
    dispatcher:
        enabled: true
        with_profiling: false
        with_transaction: false
        with_event_store: false
        with_lock: false
    event_store:
        enabled: true
```

Puis copiez ensuite le contenu de `samples/symfony/makinacorpus-goat-adapter.yaml`
dans `config/services.yaml` ou tout autre fichier lu par votre application
Symfony qui build le container.
