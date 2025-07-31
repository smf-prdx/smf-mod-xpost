# smf-mod-xpost

A SimpleMachines Forum (SMF) 2.1.x modification to embed posts from [X.com](https://x.com) (formerly Twitter) directly into forum messages.

---

## 📌 Description

**smf-mod-xpost** is a lightweight, hook-only modification for SMF 2.1.x. No patching is necessary, and it's designed to install cleanly alongside other mods.

It introduces a custom BBCode tag:

> [xpost]URL[/xpost]

When used, this tag leverages the oEmbed API[1] to fetch and embed public X.com posts in your topics.

> 🔒 To respect API throttling limits, please enable your forum’s **cache system**. This mod will store embed data in the cache to reduce requests.

---

## 📥 Installation

To install the mod:

1. Go to the [latest release page](https://github.com/smf-prdx/smf-mod-xpost/releases)
2. Download the `.zip` package
3. Upload and install via the [Package Manager](https://wiki.simplemachines.org/smf/SMF2.1:Package_manager) in your SMF admin panel

---

## ⚠️ Limitations

- Currently supports **only individual post URLs**, in the format:
https://x.com/{HANDLE}/status/{POSTID}

- Does **not** support threads, media previews, or posts behind login/authentication walls.
- API availability depends on X.com’s external oEmbed service, and may be subject to change.

---

## 🐞 Bugs

No known issues reported as of now. If you find one, feel free to open an issue on GitHub.

---

## 🧑‍💻 Author

- [Paradox](https://cientoseis.es/index.php?action=profile;area=summary;u=375) — Admin at CientoSeis forum
- [prdx](https://www.simplemachines.org/community/index.php?action=profile;area=summary;u=674744) — Contributor in SimpleMachines community

---

## 📦 Repository

The source code is available at:
[https://github.com/smf-prdx/smf-mod-xpost](https://github.com/smf-prdx/smf-mod-xpost)

---

## 🔗 References

[1]: https://publish.twitter.com — Twitter's official oEmbed API documentation
