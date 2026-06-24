#!/usr/bin/env python3
from __future__ import annotations

import json
import os
import sys
import urllib.error
import urllib.request
from pathlib import Path


def main() -> int:
    kind = sys.argv[1] if len(sys.argv) > 1 else os.getenv("NOTIFICATION_KIND", "")
    webhook_url = normalize_webhook_url(os.getenv("DISCORD_WEBHOOK_URL", ""))
    if webhook_url == "":
        print("Discord notification skipped: webhook secret is not configured.")
        return 0

    payload = build_release_payload() if kind == "release" else build_push_payload()
    data = json.dumps(payload, ensure_ascii=False).encode("utf-8")
    request = urllib.request.Request(
        webhook_url,
        data=data,
        headers={
            "Content-Type": "application/json",
            "User-Agent": "ImperaZim-Repository-Notifications/1.0",
        },
        method="POST",
    )

    try:
        with urllib.request.urlopen(request, timeout=10) as response:
            print(f"Discord notification sent with HTTP {response.status}.")
    except urllib.error.HTTPError as error:
        print(f"Discord notification failed with HTTP {error.code}; release/build will continue.")
    except urllib.error.URLError as error:
        print(f"Discord notification failed: {error.reason}; release/build will continue.")

    return 0


def normalize_webhook_url(webhook_url: str) -> str:
    webhook_url = webhook_url.strip()
    if webhook_url.endswith("/github"):
        return webhook_url[:-7]
    return webhook_url


def build_push_payload() -> dict[str, object]:
    event = read_event()
    repository = os.getenv("GITHUB_REPOSITORY", event.get("repository", {}).get("full_name", "unknown/repo"))
    branch = os.getenv("GITHUB_REF_NAME", event.get("ref", "").replace("refs/heads/", ""))
    actor = os.getenv("GITHUB_ACTOR", event.get("pusher", {}).get("name", "unknown"))
    compare_url = event.get("compare") or repository_url(repository)
    commits = event.get("commits", [])
    head = event.get("head_commit") or {}
    head_message = first_line(str(head.get("message", "No commit message.")), 180)
    head_sha = str(head.get("id", os.getenv("GITHUB_SHA", "")))[:7]

    return {
        "username": "ImperaZim Repository",
        "embeds": [
            {
                "title": f"Push: {repository}",
                "url": compare_url,
                "description": head_message,
                "color": 0x3BA55D,
                "fields": [
                    {"name": "Branch", "value": branch or "unknown", "inline": True},
                    {"name": "Actor", "value": actor, "inline": True},
                    {"name": "Commits", "value": str(len(commits)), "inline": True},
                    {"name": "Head", "value": f"`{head_sha}`", "inline": True},
                ],
            }
        ],
    }


def build_release_payload() -> dict[str, object]:
    repository = os.getenv("GITHUB_REPOSITORY", "unknown/repo")
    name = os.getenv("RELEASE_NAME", repository.rsplit("/", 1)[-1])
    version = os.getenv("RELEASE_VERSION", "").strip()
    tag = os.getenv("RELEASE_TAG", os.getenv("GITHUB_REF_NAME", "")).strip()
    assets = [asset.strip() for asset in os.getenv("RELEASE_ASSETS", "").split(",") if asset.strip()]
    release_url = f"{repository_url(repository)}/releases/tag/{tag}" if tag else repository_url(repository)
    title = f"Release: {name}" + (f" {version}" if version else "")

    fields = [
        {"name": "Repository", "value": repository, "inline": True},
        {"name": "Tag", "value": tag or "unknown", "inline": True},
        {"name": "Trigger", "value": os.getenv("GITHUB_EVENT_NAME", "unknown"), "inline": True},
    ]
    if assets:
        fields.append({"name": "Assets", "value": "\n".join(f"`{Path(asset).name}`" for asset in assets), "inline": False})

    return {
        "username": "ImperaZim Releases",
        "embeds": [
            {
                "title": title,
                "url": release_url,
                "description": "Release assets were published and verified.",
                "color": 0x5865F2,
                "fields": fields,
            }
        ],
    }


def read_event() -> dict[str, object]:
    event_path = os.getenv("GITHUB_EVENT_PATH")
    if not event_path:
        return {}

    try:
        with open(event_path, "r", encoding="utf-8") as file:
            data = json.load(file)
            return data if isinstance(data, dict) else {}
    except (OSError, json.JSONDecodeError):
        return {}


def repository_url(repository: str) -> str:
    return f"{os.getenv('GITHUB_SERVER_URL', 'https://github.com')}/{repository}"


def first_line(value: str, max_length: int) -> str:
    line = value.splitlines()[0] if value.splitlines() else value
    return line if len(line) <= max_length else line[: max_length - 3] + "..."


if __name__ == "__main__":
    raise SystemExit(main())
