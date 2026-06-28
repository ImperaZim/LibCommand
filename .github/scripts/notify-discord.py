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
    dry_run = is_truthy(os.getenv("DISCORD_DRY_RUN", ""))
    if webhook_url == "" and not dry_run:
        print("Discord notification skipped: webhook secret is not configured.")
        return 0

    payload = build_payload(kind)
    if dry_run:
        print(json.dumps(payload, ensure_ascii=False, indent=2))
        return 0

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


def build_payload(kind: str) -> dict[str, object]:
    if kind == "release":
        return build_release_payload()
    if kind in {"test", "manual", "workflow_dispatch"}:
        return build_test_payload()
    return build_push_payload()


def build_push_payload() -> dict[str, object]:
    event = read_event()
    repository = os.getenv("GITHUB_REPOSITORY", event.get("repository", {}).get("full_name", "unknown/repo"))
    branch = os.getenv("GITHUB_REF_NAME", event.get("ref", "").replace("refs/heads/", ""))
    actor = os.getenv("GITHUB_ACTOR", event.get("pusher", {}).get("name", "unknown"))
    repo_url = repository_url(repository)
    compare_url = event.get("compare") or repo_url
    commits = event.get("commits", [])
    commits = commits if isinstance(commits, list) else []
    head = event.get("head_commit") or {}
    head_message = first_line(str(head.get("message", "No commit message.")), 180)
    head_sha = str(head.get("id", os.getenv("GITHUB_SHA", "")))[:7]
    repo_name = repository.rsplit("/", 1)[-1]
    owner = repository.split("/", 1)[0] if "/" in repository else actor
    count = len(commits)
    commit_word = "commit" if count == 1 else "commits"
    title = f"[{repo_name}:{branch or 'unknown'}] {count} new {commit_word}"
    if count == 0:
        title = f"[{repo_name}:{branch or 'unknown'}] new push"

    lines = []
    commit_lines = format_commit_lines(commits, repo_url, actor)
    if commit_lines:
        lines.extend(commit_lines)
    else:
        lines.append(f"[`{head_sha}`]({commit_url(repo_url, str(head.get('id', os.getenv('GITHUB_SHA', ''))))}) {head_message} - {actor}")

    embed = {
        "author": {
            "name": owner,
            "url": f"https://github.com/{owner}",
            "icon_url": github_avatar_url(owner),
        },
        "title": title,
        "url": compare_url,
        "description": "\n".join(lines),
        "color": 0x2F81F7,
        "fields": compact_fields(
            [
                field("Repository", f"[{repository}]({repo_url})", True),
                field("Branch", f"[{branch or 'unknown'}]({branch_url(repository, branch)})", True),
                field("Actor", f"[{actor}](https://github.com/{actor})", True),
                field("Commits", str(count), True),
                field("Head", f"[`{head_sha}`]({commit_url(repo_url, str(head.get('id', os.getenv('GITHUB_SHA', ''))))})", True),
            ]
        ),
        "footer": {"text": "Repository push notification"},
    }
    timestamp = str(head.get("timestamp", "")).strip()
    if timestamp != "":
        embed["timestamp"] = timestamp

    return {
        "username": owner,
        "avatar_url": github_avatar_url(owner),
        "embeds": [embed],
    }


def build_release_payload() -> dict[str, object]:
    repository = os.getenv("GITHUB_REPOSITORY", "unknown/repo")
    owner = repository.split("/", 1)[0] if "/" in repository else "ImperaZim"
    name = os.getenv("RELEASE_NAME", repository.rsplit("/", 1)[-1])
    version = os.getenv("RELEASE_VERSION", "").strip()
    tag = os.getenv("RELEASE_TAG", os.getenv("GITHUB_REF_NAME", "")).strip()
    assets = [asset.strip() for asset in os.getenv("RELEASE_ASSETS", "").split(",") if asset.strip()]
    repo_url = repository_url(repository)
    release_url = f"{repo_url}/releases/tag/{tag}" if tag else repo_url
    display_version = version or tag or "release"
    title = f"[{name}:{display_version}] release assets published"
    description = [
        "Release assets were published and verified.",
    ]
    if assets:
        description.extend(["", "Assets:"])
        description.extend(f"- `{Path(asset).name}`" for asset in assets)

    return {
        "username": owner,
        "avatar_url": github_avatar_url(owner),
        "embeds": [
            {
                "author": {
                    "name": owner,
                    "url": f"https://github.com/{owner}",
                    "icon_url": github_avatar_url(owner),
                },
                "title": title,
                "url": release_url,
                "description": "\n".join(description),
                "color": 0x5865F2,
                "fields": compact_fields(
                    [
                        field("Repository", f"[{repository}]({repo_url})", True),
                        field("Tag", f"[`{tag or 'unknown'}`]({release_url})", True),
                        field("Actor", f"[{os.getenv('GITHUB_ACTOR', 'GitHub Actions')}](https://github.com/{os.getenv('GITHUB_ACTOR', 'github-actions')})", True),
                    ]
                ),
                "footer": {"text": f"triggered by {os.getenv('GITHUB_ACTOR', 'GitHub Actions')}"},
            }
        ],
    }


def build_test_payload() -> dict[str, object]:
    repository = os.getenv("GITHUB_REPOSITORY", "unknown/repo")
    owner = repository.split("/", 1)[0] if "/" in repository else os.getenv("GITHUB_ACTOR", "ImperaZim")
    actor = os.getenv("GITHUB_ACTOR", "GitHub Actions")
    branch = os.getenv("GITHUB_REF_NAME", "manual")
    repo_url = repository_url(repository)

    return {
        "username": owner,
        "avatar_url": github_avatar_url(owner),
        "embeds": [
            {
                "author": {
                    "name": owner,
                    "url": f"https://github.com/{owner}",
                    "icon_url": github_avatar_url(owner),
                },
                "title": f"[{repository.rsplit('/', 1)[-1]}:{branch}] notification test",
                "url": repo_url,
                "description": "Manual repository notification test from GitHub Actions.",
                "color": 0x3FB950,
                "fields": compact_fields(
                    [
                        field("Repository", f"[{repository}]({repo_url})", True),
                        field("Branch", f"[{branch}]({branch_url(repository, branch)})", True),
                        field("Actor", f"[{actor}](https://github.com/{actor})", True),
                    ]
                ),
                "footer": {"text": "Repository notification test"},
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


def branch_url(repository: str, branch: str) -> str:
    if branch == "":
        return repository_url(repository)
    return f"{repository_url(repository)}/tree/{branch}"


def commit_url(repository_url_value: str, sha: str) -> str:
    if sha == "":
        return repository_url_value
    return f"{repository_url_value}/commit/{sha}"


def github_avatar_url(owner: str) -> str:
    return f"https://github.com/{owner}.png?size=128"


def field(name: str, value: str, inline: bool = False) -> dict[str, object]:
    return {"name": name, "value": value if value.strip() else "unknown", "inline": inline}


def compact_fields(fields: list[dict[str, object]]) -> list[dict[str, object]]:
    compacted = []
    for item in fields:
        value = str(item.get("value", "")).strip()
        if value == "":
            continue
        compacted.append(item)
    return compacted[:10]


def format_commit_lines(commits: list[object], repo_url: str, fallback_author: str) -> list[str]:
    lines = []
    for commit in commits[:5]:
        if not isinstance(commit, dict):
            continue
        sha = str(commit.get("id", ""))[:7]
        full_sha = str(commit.get("id", ""))
        message = first_line(str(commit.get("message", "No commit message.")), 160)
        url = str(commit.get("url", "")) or commit_url(repo_url, full_sha)
        author_data = commit.get("author", {})
        author = fallback_author
        if isinstance(author_data, dict):
            author = str(author_data.get("username") or author_data.get("name") or fallback_author)
        lines.append(f"[`{sha}`]({url}) {message} - {author}")

    remaining = len(commits) - len(lines)
    if remaining > 0:
        lines.append(f"...and {remaining} more.")
    return lines


def first_line(value: str, max_length: int) -> str:
    line = value.splitlines()[0] if value.splitlines() else value
    return line if len(line) <= max_length else line[: max_length - 3] + "..."


def is_truthy(value: str) -> bool:
    return value.strip().lower() in {"1", "true", "yes", "on", "dry-run", "dryrun"}


if __name__ == "__main__":
    raise SystemExit(main())
